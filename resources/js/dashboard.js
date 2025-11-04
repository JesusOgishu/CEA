import { animate as anime } from 'animejs';
import interact from 'interactjs';

/**
 * Anima un elemento a su posición inicial (0,0) si el arrastre se cancela.
 */
function animateToStartPosition(target) {
  if (!target) return;

  anime({
    targets: target,
    translateX: 0,
    translateY: 0,
    duration: 350,
    easing: 'easeOutExpo',
    complete: () => {
      if (!target) return;
      target.style.transform = '';
      target.setAttribute('data-x', '0');
      target.setAttribute('data-y', '0');
    }
  });
}

/**
 * GIDs de secciones por cuadrante.
 */
const QUADRANT_SECTION_GIDS = window.QUADRANT_SECTION_GIDS || {};
console.log('📦 Secciones cargadas desde Blade:', QUADRANT_SECTION_GIDS);

/**
 * Actualiza la sección de una tarea en Asana.
 */
function updateAsanaTaskSection(taskId, projectGid, sectionGid) {
  const url = `/asana/tasks/${taskId}/move`;
  const finalSectionGid = sectionGid || null;

  fetch(url, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
    },
    body: JSON.stringify({
      project_gid: projectGid,
      section_gid: finalSectionGid
    })
  })
    .then(async response => {
      const contentType = response.headers.get('content-type') || '';
      let data;

      if (contentType.includes('application/json')) {
        data = await response.json();
      } else {
        const text = await response.text();
        console.warn('⚠️ Respuesta no JSON del servidor:', text);
        throw new Error(`Respuesta no válida del servidor (status ${response.status})`);
      }

      if (!response.ok) {
        let asanaError = data.error || 'Fallo en la solicitud de red';
        if (data.errors && data.errors.length > 0) {
          asanaError = `Asana API error: ${data.errors[0].message}`;
        }
        throw new Error(asanaError);
      }

      console.log(`✅ Tarea ${taskId} movida con éxito en Asana.`, data);
    })
    .catch(error => {
      console.error('❌ Error al actualizar la tarea en Asana:', error.message);
      const message = document.createElement('div');
      message.className =
        'fixed top-4 right-4 bg-red-600 text-white p-3 rounded shadow-lg transition-opacity duration-500 z-50';
      message.textContent = `Error al mover la tarea: ${error.message.substring(
        0,
        100
      )}... Revisa la consola.`;
      document.body.appendChild(message);
      setTimeout(() => message.remove(), 7000);
    });
}

/**
 * Inicializa arrastrar y soltar.
 */
function initDragAndDrop() {
  const draggableSelector = '.task-card';
  const dropzoneSelector = '.tasks-overview, .matrix-quadrant';

  interact(draggableSelector).draggable({
    inertia: true,
    autoScroll: true,
    listeners: {
      start(event) {
        event.target?.classList.add('is-dragging');
      },
      move(event) {
        const target = event.target;
        if (!target) return;
        const x = (parseFloat(target.getAttribute('data-x')) || 0) + event.dx;
        const y = (parseFloat(target.getAttribute('data-y')) || 0) + event.dy;
        target.style.transform = `translate(${x}px, ${y}px)`;
        target.setAttribute('data-x', x);
        target.setAttribute('data-y', y);
      },
      end(event) {
        if (!event.relatedTarget && event.target) {
          animateToStartPosition(event.target);
        }
        event.target?.classList.remove('is-dragging');
      }
    }
  });

  interact(dropzoneSelector).dropzone({
    accept: draggableSelector,
    overlap: 0.5,
    listeners: {
      dragenter(event) {
        event.target.classList.add('drop-active');
      },
      dragleave(event) {
        event.target.classList.remove('drop-active');
      },
      drop(event) {
        const draggableElement = event.relatedTarget;
        const dropzoneElement = event.target;
        const list = dropzoneElement.querySelector('.task-list, .task-cards-container');

        if (list && draggableElement) {
          draggableElement.style.transform = '';
          draggableElement.setAttribute('data-x', '0');
          draggableElement.setAttribute('data-y', '0');
          list.appendChild(draggableElement);

          const listId = list.id;
          const newSectionGid = QUADRANT_SECTION_GIDS[listId];
          const taskId = draggableElement.getAttribute('data-task-id');
          const projectGid = draggableElement.getAttribute('data-project-gid');

          if (taskId && projectGid) {
            updateAsanaTaskSection(taskId, projectGid, newSectionGid);
          } else {
            console.warn(
              '⚠️ Faltan datos esenciales para mover la tarea. Asegúrate de que estás en un proyecto filtrado.',
              { listId, newSectionGid, taskId, projectGid }
            );
          }
        }
        dropzoneElement.classList.remove('drop-active');
      }
    }
  });
}

/**
 * ⭐️ CRÍTICO ⭐️: Inicializa el selector de workspace y elimina el filtro de proyecto.
 */
function initWorkspaceSelector() {
  const select = document.getElementById('workspaceSelector');
  if (!select) return;

  select.addEventListener('change', e => {
    const workspaceGid = e.target.value;
    const currentUrl = new URL(window.location.href);

    if (workspaceGid) {
      // 1. Añadir el nuevo workspace GID a la URL
      currentUrl.searchParams.set('workspace', workspaceGid);
    } else {
      currentUrl.searchParams.delete('workspace');
    }
    currentUrl.searchParams.delete('project'); 

    // 3. Forzar la recarga de la página
    window.location.href = currentUrl.toString(); 
  });
}


/**
 * Inicializa el selector de proyectos.
 */
function initProjectSelector() {
  const select = document.getElementById('projectSelector');
  if (!select) return;

  select.addEventListener('change', e => {
    const projectGid = e.target.value;
    const currentUrl = new URL(window.location.href);

    if (projectGid) {
      currentUrl.searchParams.set('project', projectGid);
    } else {
      currentUrl.searchParams.delete('project');
    }

    // Recargamos la página (mantiene el 'workspace' que ya debe estar en la URL)
    window.location.href = currentUrl.toString();
  });
}

/**
 * Inicializa el toggle del menú lateral.
 */
function initSidebarToggle() {
  const gridContainer = document.querySelector('.grid-container');
  const menuIcon = document.querySelector('.menu-icon');
  if (gridContainer && menuIcon) {
    menuIcon.addEventListener('click', () => {
      gridContainer.classList.toggle('sidebar-open');
    });
  }
}


/**
 * Inicializa todo.
 */
document.addEventListener('DOMContentLoaded', () => {
  initSidebarToggle();
  initDragAndDrop();
  initWorkspaceSelector(); 
  initProjectSelector();
});