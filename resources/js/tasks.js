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
    
    const modal = document.getElementById('createTaskModal');
    const openBtn = document.getElementById('createTaskBtn'); 
    const closeBtn = document.getElementById('closeModalBtn');
    const form = document.getElementById('createTaskForm');
    const submitBtn = document.getElementById('submitTaskBtn');
    const errorMsgDiv = document.getElementById('modalError');
    const modalTitle = document.getElementById('modalTitle');
    
    const modalWorkspaceGid = document.getElementById('modal_workspace_gid');
    const modalProjectGid = document.getElementById('modal_project_gid');
    const modalTaskGid = document.getElementById('modal_task_gid'); 

    const workspaceSelector = document.getElementById('workspaceSelector');
    const projectSelector = document.getElementById('projectSelector');
    
    const assigneeSelect = document.getElementById('task_assignee');
    const assigneeWheeler = document.getElementById('assigneeWheeler');

    const editToggleBtn = document.getElementById('editTaskToggleBtn'); 
    const editToast = document.getElementById('editModeToast'); 
    const taskCardsContainer = document.getElementById('taskCardsContainer'); 
    const pageWrapper = document.querySelector('.task-page-wrapper'); 

    const deleteToggleBtn = document.getElementById('deleteTaskToggleBtn');
    const deleteBar = document.getElementById('deleteConfirmBar');
    const deleteCountEl = document.getElementById('deleteCount');
    const confirmDeleteBtn = document.getElementById('confirmDeleteBtn');
    const cancelDeleteBtn = document.getElementById('cancelDeleteBtn');

    let isEditMode = false;
    let isDeleteMode = false;
    let tasksToDelete = [];
    let toastTimer = null;

    if (!modal || !openBtn || !closeBtn || !form || !submitBtn || !workspaceSelector || !projectSelector || !assigneeSelect || !editToggleBtn || !editToast || !taskCardsContainer || !pageWrapper || !deleteToggleBtn || !deleteBar || !confirmDeleteBtn || !cancelDeleteBtn) {
        console.warn('Faltan elementos del DOM para inicializar todas las funciones.');
        return;
    }

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
            assigneeSelect.innerHTML = ''; 
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
                assigneeSelect.value = ''; 
            }
        } else {
            modalTitle.textContent = 'Create New Task';
            submitBtn.textContent = 'Submit Task';
            modalTaskGid.value = ''; 
            modalWorkspaceGid.value = workspaceGid;
            modalProjectGid.value = projectSelector.value;
        }
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
        if (errorMsgDiv) errorMsgDiv.style.display = 'none';
        const formData = new FormData(form);
        const csrfToken = document.querySelector('input[name="_token"]').value;
        let url;
        let method = 'POST'; 
        const taskGid = modalTaskGid.value;
        if (taskGid) {
            url = `/tasks/update/${taskGid}`;
            submitBtn.textContent = 'Updating...';
        } else {
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
            window.location.reload(); 
        })
        .catch(error => {
            console.error('Error al guardar tarea:', error);
            let msg = error.error || error.message || 'An unknown error occurred.';
            if (errorMsgDiv) {
                errorMsgDiv.textContent = msg;
                errorMsgDiv.style.display = 'block';
            }
            submitBtn.disabled = false;
            submitBtn.textContent = taskGid ? 'Update Task' : 'Submit Task';
        });
    }

    function showToast() {
        if (toastTimer) clearTimeout(toastTimer);
        editToast.classList.add('show');
        toastTimer = setTimeout(() => {
            editToast.classList.remove('show');
        }, 2000); 
    }

    function resetEditMode() {
        isEditMode = false;
        editToggleBtn.classList.remove('active');
        pageWrapper.classList.remove('edit-mode-active');
    }

    function resetDeleteMode() {
        isDeleteMode = false;
        deleteToggleBtn.classList.remove('active');
        pageWrapper.classList.remove('delete-mode-active');
        deleteBar.classList.remove('show');
        tasksToDelete = [];
        document.querySelectorAll('.prj-card.selected').forEach(card => {
            card.classList.remove('selected');
        });
        updateDeleteBar();
    }

    function toggleEditMode() {
        if (isDeleteMode) resetDeleteMode(); 
        
        isEditMode = !isEditMode;
        editToggleBtn.classList.toggle('active', isEditMode);
        pageWrapper.classList.toggle('edit-mode-active', isEditMode);

        if (isEditMode) {
            showToast();
        }
    }

    function toggleDeleteMode() {
        if (isEditMode) resetEditMode(); 

        isDeleteMode = !isDeleteMode;
        deleteToggleBtn.classList.toggle('active', isDeleteMode);
        pageWrapper.classList.toggle('delete-mode-active', isDeleteMode);
        deleteBar.classList.toggle('show', isDeleteMode);

        if (!isDeleteMode) {
            resetDeleteMode(); 
        }
    }

    function updateDeleteBar() {
        const count = tasksToDelete.length;
        deleteCountEl.textContent = count;
        confirmDeleteBtn.disabled = (count === 0);
        confirmDeleteBtn.textContent = `Delete ${count} Task(s)`;
    }

    function handleDeleteCardClick(card, taskData) {
        const taskGid = taskData.gid;
        const index = tasksToDelete.indexOf(taskGid);

        if (index > -1) {
            tasksToDelete.splice(index, 1);
            card.classList.remove('selected');
        } else {
            tasksToDelete.push(taskGid);
            card.classList.add('selected');
        }
        updateDeleteBar();
    }

    function handleBulkDelete() {
        confirmDeleteBtn.disabled = true;
        confirmDeleteBtn.textContent = 'Deleting...';

        const csrfToken = document.querySelector('input[name="_token"]').value;

        fetch('/tasks/bulk-delete', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json',
                'Content-Type': 'application/json' 
            },
            body: JSON.stringify({ 
                gids: tasksToDelete 
            })
        })
        .then(response => {
            if (!response.ok) {
                return response.json().then(err => Promise.reject(err));
            }
            return response.json();
        })
        .then(data => {
            console.log(data.message);
            window.location.reload(); 
        })
        .catch(error => {
            console.error('Error al borrar tareas:', error);
            alert('Error deleting tasks: ' + (error.error || error.message));
            confirmDeleteBtn.disabled = false;
            updateDeleteBar(); 
        });
    }

    function handleCardClick(e) {
        const card = e.target.closest('.prj-card');
        if (!card) return;
        
        const taskDataString = card.dataset.taskJson;
        if (!taskDataString) return;

        const taskData = JSON.parse(taskDataString);

        if (isEditMode) {
            openModal(taskData);
            resetEditMode(); 
        } else if (isDeleteMode) {
            handleDeleteCardClick(card, taskData);
        }
    }

    openBtn.addEventListener('click', () => openModal(null));
    closeBtn.addEventListener('click', closeModal);
    form.addEventListener('submit', handleFormSubmit);
    modal.addEventListener('click', e => {
        if (e.target === modal) closeModal();
    });

    editToggleBtn.addEventListener('click', toggleEditMode);
    deleteToggleBtn.addEventListener('click', toggleDeleteMode); 
    
    taskCardsContainer.addEventListener('click', handleCardClick);

    cancelDeleteBtn.addEventListener('click', resetDeleteMode); 
    confirmDeleteBtn.addEventListener('click', handleBulkDelete); 
}

document.addEventListener('DOMContentLoaded', () => {
    initWorkspaceSelector();
    initProjectSelector();
    initTaskModal(); 
});