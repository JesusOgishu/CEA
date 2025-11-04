/**
 * task_filter.js
 * Maneja los selectores de Workspace y Proyecto
 */

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

document.addEventListener('DOMContentLoaded', () => {
    initWorkspaceSelector();
    initProjectSelector();
});
