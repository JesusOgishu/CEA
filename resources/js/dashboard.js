import { animate as anime } from 'animejs';
import interact from 'interactjs';


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


const QUADRANT_SECTION_GIDS = window.QUADRANT_SECTION_GIDS || {};


function updateAsanaTaskSection(taskId, projectGid, sectionGid) {
  const url = `/asana/tasks/${taskId}/move`;
  const finalSectionGid = sectionGid || null;

  
  if (sectionGid === undefined) {
      console.warn('Movimiento cancelado: No estás en una vista de proyecto.');
      return;
  }
  
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
        console.warn('Respuesta no JSON del servidor:', text);
        throw new Error(`Respuesta no válida del servidor (status ${response.status})`);
      }

      if (!response.ok) {
        let asanaError = data.error || 'Fallo en la solicitud de red';
        if (data.errors && data.errors.length > 0) {
          asanaError = `Asana API error: ${data.errors[0].message}`;
        }
        throw new Error(asanaError);
      }

      console.log(`Tarea ${taskId} movida con éxito en Asana.`, data);
    })
    .catch(error => {
      console.error('Error al actualizar la tarea en Asana:', error.message);
      
    });
}


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
        
        if (!event.target.classList.contains('eisenhower-placeholder')) {
            event.target.classList.add('drop-active');
        }
      },
      dragleave(event) {
        event.target.classList.remove('drop-active');
      },
      drop(event) {
        const draggableElement = event.relatedTarget;
        const dropzoneElement = event.target;
        
        
        if (dropzoneElement.classList.contains('eisenhower-placeholder')) {
            animateToStartPosition(draggableElement);
            dropzoneElement.classList.remove('drop-active');
            return;
        }
        
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

          // Solo actualiza si la sección existe (evita errores en "All tasks")
          if (newSectionGid !== undefined) { 
              if (taskId && projectGid) {
                updateAsanaTaskSection(taskId, projectGid, newSectionGid);
              } else {
                console.warn(
                  'Faltan datos (taskId o projectGid) para mover la tarea.',
                  { listId, newSectionGid, taskId, projectGid }
                );
              }
          } else if (Object.keys(QUADRANT_SECTION_GIDS).length > 0) {
              console.warn('Zona de drop no reconocida:', listId);
              animateToStartPosition(draggableElement);
          } else {
              console.log('Movimiento dentro de "Pending" (sin API call).');
          }
        }
        dropzoneElement.classList.remove('drop-active');
      }
    }
  });
}

function initWorkspaceSelector() {
  const select = document.getElementById('workspaceSelector');
  if (!select) return;

  select.addEventListener('change', e => {
    const workspaceGid = e.target.value;
    const currentUrl = new URL(window.location.href);

    if (workspaceGid) {
      currentUrl.searchParams.set('workspace', workspaceGid);
    } else {
      currentUrl.searchParams.delete('workspace');
    }
    currentUrl.searchParams.delete('project'); 

    window.location.href = currentUrl.toString(); 
  });
}


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
    
    window.location.href = currentUrl.toString();
  });
}

function initSidebarToggle() {
  const gridContainer = document.querySelector('.grid-container');
  const menuIcon = document.querySelector('.menu-icon'); 
  const closeBtn = document.getElementById('sidebar-close-btn'); 
  const overlay = document.querySelector('.sidebar-overlay'); 

  if (gridContainer && menuIcon && closeBtn && overlay) {
    
    // abrir
    const openSidebar = () => {
      gridContainer.classList.add('sidebar-open');
    };
    
    // cerrar
    const closeSidebar = () => {
      gridContainer.classList.remove('sidebar-open');
    };

    // eventos
    menuIcon.addEventListener('click', openSidebar);
    closeBtn.addEventListener('click', closeSidebar);
    overlay.addEventListener('click', closeSidebar);
  }
}



document.addEventListener('DOMContentLoaded', () => {
  initSidebarToggle(); 
  initDragAndDrop();
  initWorkspaceSelector(); 
  initProjectSelector();
});