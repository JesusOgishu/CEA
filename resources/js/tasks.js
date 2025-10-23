/**
 * task_filter.js
 * Maneja el selector de proyectos para recargar
 * la vista de tareas con el filtro aplicado.
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

        window.location.href = currentUrl.toString();
    });
}

document.addEventListener('DOMContentLoaded', initProjectSelector);
