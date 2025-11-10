@php
    $workspaceGid = $workspaceGid ?? null;
    $workspaces = $workspaces ?? [];
@endphp

@extends('layout.layout')

@section('title', 'User Metrics')

@section('content')
<div class="metrics-container">
    <div class="metrics-header-metrics">
        <div class="workspace-filter-metrics">
            <label for="workspaceSelect">Filter by workspace</label>
            <select id="workspaceSelect" name="workspace">
                @foreach($workspaces as $ws)
                    <option value="{{ $ws['gid'] }}" {{ $ws['gid'] === $workspaceGid ? 'selected' : '' }}>
                        {{ $ws['name'] }}
                    </option>
                @endforeach
            </select>
        </div>
    </div>

    <div>
        <h2 class="metrics-section-title-metrics">Overview</h2>
        <div class="metrics-cards-grid-metrics">
            <div class="metrics-card-metrics">
                <div class="metrics-card-title-metrics">Total Tasks</div>
                <div id="metric-total-tasks" class="metrics-card-value-metrics loading-metrics">...</div>
            </div>
            <div class="metrics-card-metrics">
                <div class="metrics-card-title-metrics">Completed Tasks</div>
                <div id="metric-completed-tasks" class="metrics-card-value-metrics loading-metrics">...</div>
            </div>
            <div class="metrics-card-metrics">
                <div class="metrics-card-title-metrics">Overdue Tasks</div>
                <div id="metric-overdue-tasks" class="metrics-card-value-metrics loading-metrics">...</div>
            </div>
            <div class="metrics-card-metrics">
                <div class="metrics-card-title-metrics">Active Projects</div>
                <div id="metric-active-projects" class="metrics-card-value-metrics loading-metrics">...</div>
            </div>
        </div>
    </div>

    <div>
        <h2 class="metrics-section-title-metrics">Charts</h2>
        <div class="metrics-charts-grid-metrics">
            <div class="metrics-chart-card-metrics">
                <h3>Tasks Completed Over Time</h3>
                <div id="chart-tasks-completed" class="metrics-chart-metrics loading-metrics">
                    <div class="metrics-spinner"></div>
                </div>
            </div>
            <div class="metrics-chart-card-metrics">
                <h3>Tasks by Project</h3>
                <div id="chart-tasks-by-project" class="metrics-chart-metrics loading-metrics">
                    <div class="metrics-spinner"></div>
                </div>
            </div>
            <div class="metrics-chart-card-metrics">
                <h3>Top Assignees</h3>
                <div id="chart-top-assignees" class="metrics-chart-metrics loading-metrics">
                    <div class="metrics-spinner"></div>
                </div>
            </div>
            <div class="metrics-chart-card-metrics">
                <h3>Overdue Tasks Breakdown</h3>
                <div id="chart-overdue" class="metrics-chart-metrics loading-metrics">
                    <div class="metrics-spinner"></div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection