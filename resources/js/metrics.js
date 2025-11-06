/* ==========================================================================
   Metrics Dashboard - Asana Integration (Fixed Refresh on Workspace Change)
   With Loading State Fix
   ========================================================================== */

document.addEventListener("DOMContentLoaded", () => {
    const workspaceSelect = document.querySelector("#workspaceSelect");

    const baseUrl = '/metrics/api';
    const endpoints = (workspace) => ({
        overview: `${baseUrl}/overview?workspace=${workspace}`,
        tasksCompleted: `${baseUrl}/tasks-completed?workspace=${workspace}`,
        tasksByProject: `${baseUrl}/tasks-by-project?workspace=${workspace}`,
        topAssignees: `${baseUrl}/top-assignees?workspace=${workspace}`,
        overdue: `${baseUrl}/overdue?workspace=${workspace}`,
    });

    function setLoadingState(selector, loading = true) {
        const el = document.querySelector(selector);
        if (!el) return;
        if (loading) {
            el.textContent = 'Loading...';
            el.classList.add('loading');
        } else {
            el.classList.remove('loading');
        }
    }

    async function fetchData(url) {
        try {
            const response = await fetch(url);
            if (!response.ok) throw new Error(`HTTP ${response.status}`);
            const data = await response.json();
            return data?.data || {};
        } catch (error) {
            console.error(`[Metrics API Error] ${url}:`, error);
            return {};
        }
    }

    async function loadOverview(ep) {
        const selectors = [
            '#metric-total-tasks',
            '#metric-completed-tasks',
            '#metric-overdue-tasks',
            '#metric-active-projects'
        ];

        // Mostrar loading pequeño
        selectors.forEach(sel => setLoadingState(sel, true));

        const data = await fetchData(ep.overview);

        // Actualizar valores
        document.querySelector('#metric-total-tasks').textContent =
            (data.open_tasks ?? 0) + (data.completed_last_days ?? 0);
        document.querySelector('#metric-completed-tasks').textContent = data.completed_last_days ?? 0;
        document.querySelector('#metric-overdue-tasks').textContent = data.overdue_tasks ?? 0;
        document.querySelector('#metric-active-projects').textContent = data.active_projects ?? 0;

        // Quitar clase .loading para que el tamaño grande vuelva
        selectors.forEach(sel => setLoadingState(sel, false));
    }

    async function renderTasksCompletedChart(ep) {
        const container = document.querySelector("#chart-tasks-completed");
        setLoadingState("#chart-tasks-completed", true);

        const data = await fetchData(ep.tasksCompleted);
        container.textContent = '';
        const labels = data.labels ?? [];
        const values = data.series ?? [];
        if (!labels.length || !values.length) {
            container.textContent = 'No data available';
            return;
        }

        new ApexCharts(container, {
            chart: { type: 'line', height: 300, toolbar: { show: false } },
            series: [{ name: 'Completed Tasks', data: values }],
            xaxis: { categories: labels, title: { text: 'Days' } },
            yaxis: { title: { text: 'Tasks' } },
            stroke: { curve: 'smooth', width: 3 },
            colors: ['#00b894'],
        }).render();

        setLoadingState("#chart-tasks-completed", false);
    }

    async function renderTasksByProjectChart(ep) {
        const container = document.querySelector("#chart-tasks-by-project");
        setLoadingState("#chart-tasks-by-project", true);

        const data = await fetchData(ep.tasksByProject);
        container.textContent = '';
        const labels = data.map(d => d.project_name);
        const values = data.map(d => d.total_tasks);
        if (!labels.length || !values.length) {
            container.textContent = 'No data available';
            return;
        }

        new ApexCharts(container, {
            chart: { type: 'bar', height: 300, toolbar: { show: false } },
            series: [{ name: 'Tasks', data: values }],
            xaxis: { categories: labels, title: { text: 'Projects' } },
            plotOptions: { bar: { borderRadius: 4, horizontal: false } },
            colors: ['#0984e3'],
        }).render();

        setLoadingState("#chart-tasks-by-project", false);
    }

    async function renderTopAssigneesChart(ep) {
        const container = document.querySelector("#chart-top-assignees");
        setLoadingState("#chart-top-assignees", true);

        const data = await fetchData(ep.topAssignees);
        container.textContent = '';
        const labels = data.map(d => d.name);
        const values = data.map(d => d.count);
        if (!labels.length || !values.length) {
            container.textContent = 'No data available';
            return;
        }

        new ApexCharts(container, {
            chart: { type: 'bar', height: 300, toolbar: { show: false } },
            series: [{ name: 'Completed Tasks', data: values }],
            xaxis: { categories: labels, title: { text: 'Users' } },
            plotOptions: { bar: { horizontal: true, borderRadius: 4 } },
            colors: ['#6c5ce7'],
        }).render();

        setLoadingState("#chart-top-assignees", false);
    }

    async function renderOverdueChart(ep) {
        const container = document.querySelector("#chart-overdue");
        setLoadingState("#chart-overdue", true);

        const data = await fetchData(ep.overdue);
        container.textContent = '';
        const labels = data.map(d => d.name ?? 'Unnamed Task');
        const values = data.map(() => 1);
        if (!labels.length) {
            container.textContent = 'No data available';
            return;
        }

        new ApexCharts(container, {
            chart: { type: 'donut', height: 300 },
            series: values,
            labels: labels,
            colors: ['#d63031', '#fdcb6e', '#e17055', '#fab1a0', '#ff7675'],
            legend: { position: 'bottom' },
        }).render();

        setLoadingState("#chart-overdue", false);
    }

    async function initDashboard(workspace) {
        document.querySelectorAll(".apexcharts-canvas").forEach(e => e.remove());
        const ep = endpoints(workspace);
        await loadOverview(ep);
        await renderTasksCompletedChart(ep);
        await renderTasksByProjectChart(ep);
        await renderTopAssigneesChart(ep);
        await renderOverdueChart(ep);
    }

    const urlParams = new URLSearchParams(window.location.search);
    const currentWorkspace = urlParams.get("workspace") || workspaceSelect?.value || '';

    if (workspaceSelect) workspaceSelect.value = currentWorkspace;
    initDashboard(currentWorkspace);

    if (workspaceSelect) {
        workspaceSelect.addEventListener("change", (e) => {
            const workspace = e.target.value;
            window.location.href = `/metrics?workspace=${workspace}`;
        });
    }
});
