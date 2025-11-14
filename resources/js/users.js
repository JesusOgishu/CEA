/**
 * users.js
 * Manejo del perfil Asana: 
 */
document.addEventListener('DOMContentLoaded', () => {
    // 👇 ¡AQUÍ ESTÁ EL "GUARDIA"! 👇
    // Buscamos un ID que SÓLO exista en la página de perfil.
    const profilePageGuard = document.getElementById('user-info-card');

    // Si SÍ lo encuentra, corre el script. Si NO, no hace nada.
    if (profilePageGuard) {
        loadUserInfo();
    }
});

/**
 * Llamada AJAX 
 */
async function loadUserInfo() {
    const detailsElement = document.getElementById('user-details');
    const errorElement = document.getElementById('error-message');
    const errorDetailElement = document.getElementById('error-detail');
    const loadingSection = document.getElementById('loading-section');

    // Mostrar loading
    if (loadingSection) loadingSection.style.display = 'block';
    if (detailsElement) detailsElement.style.display = 'none';
    if (errorElement) errorElement.style.display = 'none';

    try {
        const response = await fetch('/api/asana/user-info', {
            method: 'GET',
            headers: { 'Accept': 'application/json' }
        });

        if (!response.ok) {
            let errorText = `Error HTTP ${response.status}.`;
            try {
                const errorData = await response.json();
                errorText += ` ${errorData.error || response.statusText}`;
            } catch {
                errorText += ` ${response.statusText}`;
            }
            throw new Error(errorText);
        }

        const data = await response.json();
        renderUserInfo(data);

    } catch (error) {
        console.error('Error al cargar datos de Asana:', error);
        if (errorElement) {
            errorElement.style.display = 'block';
            if (errorDetailElement) errorDetailElement.textContent = error.message;
        }
    } finally {
        if (loadingSection) loadingSection.style.display = 'none';
    }
}


function renderUserInfo(data) {
    const detailsElement = document.getElementById('user-details');
    const photoElement = document.getElementById('profile-photo');
    const workspacesList = document.getElementById('workspaces-list');
    const teamList = document.getElementById('team-list');
    
  
    const userRoleElement = document.getElementById('user-role');
    const aboutMeElement = document.getElementById('user-about-me');
    const activeProjectsList = document.getElementById('active-projects-list');

    
    document.getElementById('user-name').textContent = data.name || 'Usuario desconocido';
    document.getElementById('user-email').textContent = data.email || '';

    
    const initials = data.name ? data.name.split(' ').map(n => n[0]).join('') : 'U';
    
    if (data.photo_url) {
        photoElement.src = data.photo_url;
    } else {
        photoElement.src = `https://placehold.co/100x100/969696/FFFFFF?text=${initials}`;
        photoElement.onerror = null; 
    }


    if (userRoleElement) {
        userRoleElement.textContent = data.role && data.role !== 'Rol no especificado' ? data.role : '';
    }
    
    if (aboutMeElement) {
        aboutMeElement.innerHTML = data.about_me ? data.about_me.replace(/\n/g, '<br>') : 'Aún no hay una descripción personal.';
    }


    // Workspaces 
    workspacesList.innerHTML = '';
    if (data.workspaces && data.workspaces.length > 0) {
        data.workspaces.forEach(ws => {
            const span = document.createElement('span');
            span.classList.add('workspace-tag');
            span.textContent = ws.name;
            workspacesList.appendChild(span);
        });
    } else {
        workspacesList.textContent = 'No asociado a ningún workspace.';
    }
    teamList.innerHTML = '';
    if (data.user_teams && data.user_teams.length > 0) {
        const ul = document.createElement('ul');
        ul.classList.add('user-projects-list'); 
        
        data.user_teams.forEach(team => {
            const li = document.createElement('li');
            li.classList.add('user-team-item'); 
            li.textContent = team.name;
            ul.appendChild(li);
        });
        teamList.appendChild(ul); 
    } else {
        teamList.textContent = 'No forma parte de ningún equipo.';
    }

    // Proyectos Activos
    if (activeProjectsList) {
        activeProjectsList.innerHTML = '';
        if (data.active_projects && data.active_projects.length > 0) {
            data.active_projects.forEach(project => {
                const li = document.createElement('li');
                li.classList.add('project-item');
                
                const nameSpan = document.createElement('span');
                nameSpan.textContent = project.name;
                nameSpan.classList.add('font-semibold');
                
                const dueDateSpan = document.createElement('span');
                dueDateSpan.classList.add('text-sm', 'text-gray-500', 'ml-2');
                if (project.due_date) {
                    dueDateSpan.textContent = `(Vence: ${new Date(project.due_date).toLocaleDateString()})`;
                }

                let statusBadge = '';
                if (project.current_status && project.current_status.text) {
                    const statusColor = project.current_status.color || 'gray';
                    statusBadge = `<span class="status-badge status-${statusColor.toLowerCase()}">${project.current_status.text}</span>`;
                }

                li.innerHTML = nameSpan.outerHTML + dueDateSpan.outerHTML + statusBadge;
                activeProjectsList.appendChild(li);
            });
        } else {
            const li = document.createElement('li');
            li.textContent = 'Actualmente no tienes proyectos activos asignados.';
            activeProjectsList.appendChild(li);
        }
    }
    
    // Mostrar detalles
    if (detailsElement) detailsElement.style.display = 'block';
}