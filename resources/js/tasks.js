/**
 * task_filter.js
 * Maneja filtros y modal de creación de tareas (con carga de usuarios).
 */

// --- 1. LÓGICA DE FILTROS ---

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

// --- 2. LÓGICA DEL MODAL ---

function initCreateTaskModal() {
    // Elementos del Modal
    const modal = document.getElementById('createTaskModal');
    const openBtn = document.getElementById('createTaskBtn');
    const closeBtn = document.getElementById('closeModalBtn');
    const form = document.getElementById('createTaskForm');
    const submitBtn = document.getElementById('submitTaskBtn');
    const errorMsgDiv = document.getElementById('modalError');

    // Elementos de Filtros (para leer)
    const workspaceSelector = document.getElementById('workspaceSelector');
    const projectSelector = document.getElementById('projectSelector');
    
    // Campos del Formulario (Inputs)
    const modalWorkspaceGid = document.getElementById('modal_workspace_gid');
    const modalProjectGid = document.getElementById('modal_project_gid');
    
    // Elementos del Asignado
    const assigneeSelect = document.getElementById('task_assignee');
    const assigneeWheeler = document.getElementById('assigneeWheeler');

    // Chequeo de elementos
    if (!modal || !openBtn || !closeBtn || !form || !submitBtn || !workspaceSelector || !projectSelector || !assigneeSelect) {
        console.warn('Faltan elementos del modal para inicializar.');
        return;
    }

    /**
     * Carga la lista de usuarios desde nuestra API
     */
    async function loadAssignees(workspaceGid) {
        if (!workspaceGid) return;

        // 1. Mostrar spinner y deshabilitar select
        assigneeWheeler.style.display = 'block';
        assigneeSelect.disabled = true;
        assigneeSelect.innerHTML = '<option value="">Loading users...</option>';

        try {
            const response = await fetch(`/api/asana/workspace-users?workspace_gid=${workspaceGid}`, {
                headers: { 'Accept': 'application/json' }
            });

            if (!response.ok) {
                throw new Error('Could not fetch users.');
            }

            const users = await response.json();

            // 2. Rellenar el select
            assigneeSelect.innerHTML = ''; // Limpiar "Loading..."

            // Añadir opción "Asignar a mí" (usa "me" como GID especial)
            const meOption = new Option('Assign to Me (Default)', 'me');
            meOption.selected = true; // Seleccionada por defecto
            assigneeSelect.add(meOption);

            // Añadir opción "Sin asignar"
            assigneeSelect.add(new Option('Unassigned', ''));

            // Añadir el resto de usuarios
            users.forEach(user => {
                assigneeSelect.add(new Option(user.name, user.gid));
            });

        } catch (error) {
            console.error('Error loading assignees:', error);
            assigneeSelect.innerHTML = '<option value="">Error loading users</option>';
        } finally {
            // 3. Ocultar spinner y habilitar select
            assigneeWheeler.style.display = 'none';
            assigneeSelect.disabled = false;
        }
    }

    /**
     * Abre el modal
     */
    function openModal() {
        const workspaceGid = workspaceSelector.value;
        const projectGid = projectSelector.value;

        // Rellenar campos ocultos
        if (modalWorkspaceGid) modalWorkspaceGid.value = workspaceGid;
        if (modalProjectGid) modalProjectGid.value = projectGid;

        // Limpiar formulario
        form.reset(); 
        if (errorMsgDiv) {
            errorMsgDiv.style.display = 'none';
            errorMsgDiv.textContent = '';
        }
        submitBtn.disabled = false;
        submitBtn.textContent = 'Submit Task';

        // Cargar usuarios
        loadAssignees(workspaceGid);

        // Mostrar modal
        modal.style.display = 'flex';
        setTimeout(() => modal.classList.add('open'), 10);
    }

    /**
     * Cierra el modal
     */
    function closeModal() {
        modal.classList.remove('open');
        setTimeout(() => modal.style.display = 'none', 300);
    }

    /**
     * Maneja el envío del formulario
     */
    function handleFormSubmit(e) {
        e.preventDefault(); 
        submitBtn.disabled = true;
        submitBtn.textContent = 'Creating...';
        if (errorMsgDiv) errorMsgDiv.style.display = 'none';

        const formData = new FormData(form);
        const csrfToken = document.querySelector('input[name="_token"]').value;

        fetch('/tasks/store', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json',
            },
            body: formData
        })
        .then(response => {
            if (!response.ok) {
                return response.json().then(err => Promise.reject(err));
            }
            return response.json();
        })
        .then(data => {
            console.log('Tarea creada:', data);
            closeModal();
            window.location.reload(); 
        })
        .catch(error => {
            console.error('Error al crear tarea:', error);
            let msg = error.error || error.message || 'An unknown error occurred.';
            
            if (errorMsgDiv) {
                errorMsgDiv.textContent = msg;
                errorMsgDiv.style.display = 'block';
            }
            
            submitBtn.disabled = false;
            submitBtn.textContent = 'Submit Task';
        });
    }

    // --- Asignar eventos ---
    openBtn.addEventListener('click', openModal);
    closeBtn.addEventListener('click', closeModal);
    form.addEventListener('submit', handleFormSubmit);

    modal.addEventListener('click', e => {
        if (e.target === modal) {
            closeModal();
        }
    });
}

// --- 3. INICIALIZADOR GENERAL ---
document.addEventListener('DOMContentLoaded', () => {
    initWorkspaceSelector();
    initProjectSelector();
    initCreateTaskModal();
});