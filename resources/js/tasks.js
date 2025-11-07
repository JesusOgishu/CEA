/**
 * task_filter.js
 * Maneja filtros y modal de creación de tareas.
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
    const modal = document.getElementById('createTaskModal');
    const openBtn = document.getElementById('createTaskBtn');
    const closeBtn = document.getElementById('closeModalBtn');
    const form = document.getElementById('createTaskForm');
    const submitBtn = document.getElementById('submitTaskBtn');
    const errorMsgDiv = document.getElementById('modalError');

    const workspaceSelector = document.getElementById('workspaceSelector');
    const projectSelector = document.getElementById('projectSelector');
    
    const modalWorkspaceGid = document.getElementById('modal_workspace_gid');
    const modalProjectGid = document.getElementById('modal_project_gid');

    if (!modal || !openBtn || !closeBtn || !form || !submitBtn || !workspaceSelector || !projectSelector) {
        console.warn('Faltan elementos del modal para inicializar.');
        return;
    }

    function openModal() {
        const workspaceGid = workspaceSelector.value;
        const projectGid = projectSelector.value;

        if (modalWorkspaceGid) modalWorkspaceGid.value = workspaceGid;
        if (modalProjectGid) modalProjectGid.value = projectGid;

        form.reset(); 
        if (errorMsgDiv) {
            errorMsgDiv.style.display = 'none';
            errorMsgDiv.textContent = '';
        }
        submitBtn.disabled = false;
        submitBtn.textContent = 'Submit Task';

        modal.style.display = 'flex';
        setTimeout(() => modal.classList.add('open'), 10);
    }

    function closeModal() {
        modal.classList.remove('open');
        setTimeout(() => modal.style.display = 'none', 300);
    }

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