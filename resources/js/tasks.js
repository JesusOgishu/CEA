

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


function initTaskModal() {
    
    //Elementos del Modal
    const modal = document.getElementById('createTaskModal');
    const openBtn = document.getElementById('createTaskBtn'); 
    const closeBtn = document.getElementById('closeModalBtn');
    const form = document.getElementById('createTaskForm');
    const submitBtn = document.getElementById('submitTaskBtn');
    const errorMsgDiv = document.getElementById('modalError');
    const modalTitle = document.getElementById('modalTitle');
    
    // Campos Ocultos 
    const modalWorkspaceGid = document.getElementById('modal_workspace_gid');
    const modalProjectGid = document.getElementById('modal_project_gid');
    const modalTaskGid = document.getElementById('modal_task_gid'); 

    // Filtros 
    const workspaceSelector = document.getElementById('workspaceSelector');
    const projectSelector = document.getElementById('projectSelector');
    
    //Campo Asignado
    const assigneeSelect = document.getElementById('task_assignee');
    const assigneeWheeler = document.getElementById('assigneeWheeler');

    // Edición
    const editToggleBtn = document.getElementById('editTaskToggleBtn'); 
    const editToast = document.getElementById('editModeToast'); 
    const taskCardsContainer = document.getElementById('taskCardsContainer'); 
    const pageWrapper = document.querySelector('.task-page-wrapper'); 

    // Estado 
    let isEditMode = false;
    let toastTimer = null;

    if (!modal || !openBtn || !closeBtn || !form || !submitBtn || !workspaceSelector || !projectSelector || !assigneeSelect || !editToggleBtn || !editToast || !taskCardsContainer || !pageWrapper) {
        console.warn('Faltan elementos del DOM para inicializar el modal o el modo edición.');
        return;
    }

    /**
     * Carga la lista de usuarios desde nuestra API
     */
    async function loadAssignees(workspaceGid) {
        if (!workspaceGid) return;

        assigneeWheeler.style.display = 'block';
        assigneeSelect.disabled = true;
        assigneeSelect.innerHTML = '<option value="">Loading users...</option>';

        try {
            const response = await fetch(`/api/asana/workspace-users?workspace_gid=${workspaceGid}`, {
                headers: { 'Accept': 'application/json' }
            });
            if (!response.ok) throw new Error('Could not fetch users.');
            
            const users = await response.json();
            assigneeSelect.innerHTML = ''; // Limpiar

            const meOption = new Option('Assign to Me (Default)', 'me');
            meOption.selected = true; 
            assigneeSelect.add(meOption);
            assigneeSelect.add(new Option('Unassigned', ''));
            
            users.forEach(user => {
                assigneeSelect.add(new Option(user.name, user.gid));
            });

        } catch (error) {
            console.error('Error loading assignees:', error);
            assigneeSelect.innerHTML = '<option value="">Error loading users</option>';
        } finally {
            assigneeWheeler.style.display = 'none';
            assigneeSelect.disabled = false;
        }
    }

    /**
     * Abre el modal. Si recibe 'task', entra en Modo Edición.
     * Si 'task' es null, entra en Modo Creación.
     */
    async function openModal(task = null) {
        const workspaceGid = workspaceSelector.value;
        
        
        form.reset(); 
        if (errorMsgDiv) errorMsgDiv.style.display = 'none';
        submitBtn.disabled = false;
        
        await loadAssignees(workspaceGid);

        
        if (task) {
            
            modalTitle.textContent = 'Edit Task';
            submitBtn.textContent = 'Update Task';
            
            
            form.task_name.value = task.name;
            form.notes.value = task.notes || ''; 
            modalTaskGid.value = task.gid; 


            if (task.assignee && task.assignee.gid) {
                assigneeSelect.value = task.assignee.gid;

                
                if (assigneeSelect.value !== task.assignee.gid) {
                    const tempOption = new Option(task.assignee.name, task.assignee.gid);
                    assigneeSelect.add(tempOption);
                    assigneeSelect.value = task.assignee.gid;
                }
            } else {
                assigneeSelect.value = ''; // Sin asignar
            }

        } else {
            
            modalTitle.textContent = 'Create New Task';
            submitBtn.textContent = 'Submit Task';
            modalTaskGid.value = ''; 
            
            
            modalWorkspaceGid.value = workspaceGid;
            modalProjectGid.value = projectSelector.value;
        }

        //Mostrar modal
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
     * Maneja el envío del formulario (AHORA PARA CREAR Y EDITAR)
     */
    function handleFormSubmit(e) {
        e.preventDefault(); 
        submitBtn.disabled = true;
        if (errorMsgDiv) errorMsgDiv.style.display = 'none';

        const formData = new FormData(form);
        const csrfToken = document.querySelector('input[name="_token"]').value;

        let url;
        let method = 'POST'; 
        
        const taskGid = modalTaskGid.value;

        if (taskGid) {
            // --- MODO EDICIÓN ---
            url = `/tasks/update/${taskGid}`;
            submitBtn.textContent = 'Updating...';
        } else {
            // --- MODO CREACIÓN ---
            url = '/tasks/store';
            submitBtn.textContent = 'Creating...';
        }

        fetch(url, {
            method: method,
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
            console.log('Respuesta:', data);
            closeModal();
            window.location.reload(); // Recargar para ver cambios
        })
        .catch(error => {
            console.error('Error al guardar tarea:', error);
            let msg = error.error || error.message || 'An unknown error occurred.';
            
            if (errorMsgDiv) {
                errorMsgDiv.textContent = msg;
                errorMsgDiv.style.display = 'block';
            }
            
            submitBtn.disabled = false;
            // Restaurar texto del botón
            submitBtn.textContent = taskGid ? 'Update Task' : 'Submit Task';
        });
    }

    

    /**
     * Muestra el letrero (toast)
     */
    function showToast() {
        if (toastTimer) clearTimeout(toastTimer);
        editToast.classList.add('show');
        toastTimer = setTimeout(() => {
            editToast.classList.remove('show');
        }, 2000); 
    }

    /**
     * Activa o desactiva el Modo Edición
     */
    function toggleEditMode() {
        isEditMode = !isEditMode;
        editToggleBtn.classList.toggle('active', isEditMode);
        pageWrapper.classList.toggle('edit-mode-active', isEditMode);

        if (isEditMode) {
            showToast();
        }
    }

    /**
     * Manejador de clic en las tarjetas
     */
    function handleCardClick(e) {
        if (!isEditMode) return; 

        const card = e.target.closest('.prj-card');
        if (!card) return;
        
        const taskDataString = card.dataset.taskJson;
        if (!taskDataString) {
            console.error('La tarjeta no tiene data-task-json.');
            return;
        }
        
        const taskData = JSON.parse(taskDataString);
        
        // Abrir el modal 
        openModal(taskData);
        
        // Salir del modo edición
        toggleEditMode();
    }

    
   
    openBtn.addEventListener('click', () => openModal(null)); // null = Modo Creación
    
    // Eventos del modal
    closeBtn.addEventListener('click', closeModal);
    form.addEventListener('submit', handleFormSubmit);
    modal.addEventListener('click', e => {
        if (e.target === modal) {
            closeModal();
        }
    });

    editToggleBtn.addEventListener('click', toggleEditMode);
    taskCardsContainer.addEventListener('click', handleCardClick);
}



document.addEventListener('DOMContentLoaded', () => {
    initWorkspaceSelector();
    initProjectSelector();
    initTaskModal(); 
});