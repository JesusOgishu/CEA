

document.addEventListener('DOMContentLoaded', () => {

    
    const modal = document.getElementById('teammateTasksModal');
    const modalTitle = document.getElementById('teammateModalTitle');
    const modalBody = document.getElementById('teammateModalBody');
    const closeBtn = document.getElementById('closeTeammateModalBtn');
    const grid = document.querySelector('.teammates-grid-container');
    const workspaceSelector = document.getElementById('workspaceSelector');

    
    const spinnerHTML = '<div class="tk-modal-spinner-container"><div class="tk-modal-wheeler"></div></div>';

   
    if (!modal || !closeBtn || !grid || !workspaceSelector) {
        console.warn('Faltan elementos del DOM para el modal de teammates.');
        return;
    }

    workspaceSelector.addEventListener('change', e => {
        const workspaceGid = e.target.value;
        window.location.href = `/teammates?workspace=${workspaceGid}`;
    });

   

    
    async function openTeammateModal(userGid, userName) {
        
        modalTitle.textContent = `Loading ${userName}'s tasks...`;
        modalBody.innerHTML = spinnerHTML;
        modal.style.display = 'flex';
        setTimeout(() => modal.classList.add('open'), 10);
        
        const workspaceGid = workspaceSelector.value;

        
        try {
            const response = await fetch(`/api/teammate-tasks/${userGid}?workspace=${workspaceGid}`, {
                headers: { 'Accept': 'application/json' }
            });
            
            if (!response.ok) {
                throw new Error('Failed to fetch tasks.');
            }
            
            const result = await response.json();
            const tasks = result.data || [];
            modalTitle.textContent = `${userName}'s Open Tasks (${tasks.length})`;
            renderTasks(tasks);

        } catch (error) {
            console.error('Error fetching teammate tasks:', error);
            modalTitle.textContent = 'Error';
            modalBody.innerHTML = '<p style="color: red; text-align: center;">Could not load tasks.</p>';
        }
    }

    
    function renderTasks(tasks) {
        if (tasks.length === 0) {
            modalBody.innerHTML = '<p style="text-align: center; color: #777; padding: 2rem 0;">This teammate has no open tasks.</p>';
            return;
        }
        
        // lista
        let html = '<div class="teammate-task-list">';
        tasks.forEach(task => {
            html += `
                <div class="teammate-task-item">
                    <div class="teammate-task-item__info">
                        <span class="teammate-task-item__name">${task.name}</span>
                        <span class="teammate-task-item__meta">
                            <strong>Project:</strong> ${task.project} | 
                            <strong>Section:</strong> ${task.section} | 
                            <strong>Due:</strong> ${task.due_on}
                        </span>
                    </div>
                    <div class="teammate-task-item__link">
                        <a href="${task.url}" target="_blank" rel="noopener">Open</a>
                    </div>
                </div>
            `;
        });
        html += '</div>';
        
        modalBody.innerHTML = html;
    }

    
    function closeTeammateModal() {
        modal.classList.remove('open');
        setTimeout(() => modal.style.display = 'none', 300);
    }

   

    // grid
    grid.addEventListener('click', (e) => {
        const card = e.target.closest('.teammate-card');
        if (card) {
            const userGid = card.dataset.userGid;
            const userName = card.dataset.userName;
            if (userGid) {
                openTeammateModal(userGid, userName);
            }
        }
    });

    // close modal
    closeBtn.addEventListener('click', closeTeammateModal);
    modal.addEventListener('click', e => {
        if (e.target === modal) {
            closeTeammateModal();
        }
    });

});