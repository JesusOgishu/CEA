@extends('layout.layout')

@section('title', 'User Tasks')

@section('content')
<div class="task-page-wrapper">

    <div class="filters-container mb-4" style="margin-bottom: 2rem;">

        @if(isset($workspaces) && count($workspaces) > 1)
        <div class="workspace-filter filter-item">
            <label for="workspaceSelector" class="font-weight-bold">Workspace:</label>
            <select id="workspaceSelector" class="form-control" style="width: 250px;">
                @foreach ($workspaces as $ws)
                    <option 
                        value="{{ $ws['gid'] }}" 
                        {{ request('workspace') == $ws['gid'] ? 'selected' : '' }}
                    >
                        {{ $ws['name'] }}
                    </option>
                @endforeach
            </select>
        </div>
        @endif

        <div class="project-filter filter-item">
            <label for="projectSelector" class="font-weight-bold">Project:</label>
            <select id="projectSelector" class="form-control" style="width: 300px;">
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

    @if(empty($tasks))
        <p class="text-muted text-center mt-5">No available tasks found.</p>
    @else
        <div class="prj-cards-container">
            @foreach ($tasks as $task)
                <div class="prj-card">
                    <div class="prj-card__header">
                        <p class="prj-card__title">{{ $task['name'] ?? 'Untitled Task' }}</p>
                        <span class="prj-card__section">{{ $task['section_name'] }}</span>
                    </div>

                    <div class="prj-card__projects">
                        <strong>Project:</strong>
                        @if(!empty($task['projects']))
                            <ul>
                                @foreach ($task['projects'] as $project)
                                    <li>
                                        @if(!empty($project['permalink_url']))
                                            <a href="{{ $project['permalink_url'] }}" target="_blank">
                                                {{ $project['name'] ?? 'Unnamed project' }}
                                            </a>
                                        @else
                                            {{ $project['name'] ?? 'Unnamed project' }}
                                        @endif
                                    </li>
                                @endforeach
                            </ul>
                        @else
                            <p>—</p>
                        @endif
                    </div>

                    <div class="prj-card__description">
                        <strong>Description:</strong>
                        <p>{{ $task['notes'] ?? 'No description' }}</p>
                    </div>

                    <div class="prj-card__footer">
                        <div>
                            <small>Due:</small>
                            <strong>{{ $task['due_on'] ?? '—' }}</strong>
                        </div>
                        
                        @if(!empty($task['assignee_name']))
                            <div>
                                <small>Assignee:</small>
                                <strong>{{ $task['assignee_name'] }}</strong>
                            </div>
                        @endif
                        <div class="prj-card__link">
                            @if(!empty($task['permalink_url']))
                                <a href="{{ $task['permalink_url'] }}" target="_blank" rel="noopener">Open Task</a>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>

@endsection