@extends('layout.layout')

@section('title', 'Admin Dashboard')

@section('content')

<div class="header-filters-container" style="display: flex; gap: 20px; align-items: center; margin-bottom: 20px;">

    <div class="workspaces-container d-flex align-items-center">
        <label for="workspaceSelector" class="mr-3 font-weight-bold" style="margin-right: 10px; margin-bottom: 0;">Filter by Workspace:</label>
        <select id="workspaceSelector" class="form-control" style="width: 300px;">
            @forelse ($workspaces as $workspace)
                <option 
                    value="{{ $workspace['gid'] }}" 
                    {{ $workspaceId == $workspace['gid'] ? 'selected' : '' }}
                >
                    {{ $workspace['name'] }}
                </option>
            @empty
                <option value="">No Workspaces found</option>
            @endforelse
        </select>
    </div>

    <div class="projects-container d-flex align-items-center">
        <label for="projectSelector" class="mr-3 font-weight-bold" style="margin-right: 10px; margin-bottom: 0;">Filter by Project:</label>
        <select id="projectSelector" class="form-control" style="width: 300px;">
            <option value="" {{ !request('project') ? 'selected' : '' }}>
                All my tasks in this Workspace
            </option>
            @foreach ($projects as $project)
                <option 
                    value="{{ $project['gid'] }}" 
                    {{ request('project') == $project['gid'] ? 'selected' : '' }}
                >
                    {{ $project['name'] }}
                </option>
            @endforeach
        </select>
    </div>

</div>


<div class="dashboard-grid">
    
    <div class="tasks-overview">
        <h3 class="font-weight-bold">Pending Tasks</h3>
        <ul id="pending-tasks-list" class="task-cards-container">
            @forelse ($tasksByQuadrant['pending'] as $task)
                <li class="task-card"
                    data-task-id="{{ $task['gid'] }}"
                    data-project-gid="{{ $task['project_gid'] }}">
                    <a href="{{ $task['permalink_url'] }}" target="_blank">
                        <p>{{ $task['name'] }}</p>
                    </a>
                    <small>{{ $task['project_name'] }} | <strong>Updated:</strong> {{ $task['updated'] }}</small>
                </li>
            @empty
                <li class="p-3 text-muted">No unclassified pending tasks.</li>
            @endforelse
        </ul>
    </div>

    <div class="eisenhower-matrix">
        <div class="matrix-grid">

            @foreach (['do' => 'Do Now (Important & Urgent)', 'decide' => 'Decide (Important, Not Urgent)', 'delegate' => 'Delegate (Not Important, Urgent)', 'delete' => 'Delete (Not Important, Not Urgent)'] as $key => $label)
                <div class="matrix-quadrant {{ $key }}">
                    <h4>{{ $label }}</h4>
                    <ul id="{{ $key }}-list" class="task-list">
                        @forelse ($tasksByQuadrant[$key] as $task)
                            <li class="task-card"
                                data-task-id="{{ $task['gid'] }}"
                                data-project-gid="{{ $task['project_gid'] }}">
                                <a href="{{ $task['permalink_url'] }}" target="_blank">
                                    <p>{{ $task['name'] }}</p>
                                </a>
                                <small>{{ $task['project_name'] }} | <strong>Updated:</strong> {{ $task['updated'] }}</small>
                            </li>
                        @empty
                        @endforelse
                    </ul>
                </div>
            @endforeach

        </div>
    </div>
</div>

<script>
    window.QUADRANT_SECTION_GIDS = {!! $sectionMapJson !!};
    console.log('', window.QUADRANT_SECTION_GIDS);
</script>

@endsection