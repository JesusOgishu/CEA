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
                <option value="" {{ !request('project') ? 'selected' : '' }}>
                    All my tasks in Workspace
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
        
        <div class="tk-create-container"> 
            <button id="createTaskBtn" class="tk-btn-primary"> 
                Create Task
            </button>
            
            <button id="editTaskToggleBtn" class="tk-btn-secondary">
                Edit Task
            </button>
        </div>

    </div>

    @if(empty($tasks))
        <p class="text-muted text-center mt-5">No available tasks found.</p>
    @else
        <div id="taskCardsContainer" class="prj-cards-container"> 
            @foreach ($tasks as $task)
                
                <div class="prj-card" data-task-json='@json($task)'>
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

<div id="createTaskModal" class="tk-modal-overlay" style="display: none;"> 
    <div class="tk-modal-content"> 
        <div class="tk-modal-header"> 
            <h3 id="modalTitle">Create New Task</h3> 
            <button id="closeModalBtn" class="tk-modal-close">&times;</button> 
        </div>
        <form id="createTaskForm">
            @csrf
            <div class="tk-form-group"> 
                <label for="task_name">Task Name (required)</label>
                <input type="text" id="task_name" name="name" class="form-control" required>
            </div>
            <div class="tk-form-group">
                <label for="task_assignee">Assignee</label>
                <div class="tk-select-wrapper">
                    <select id="task_assignee" name="assignee_gid" class="form-control" disabled>
                        <option value="">Loading users...</option>
                    </select>
                    <div id="assigneeWheeler" class="tk-modal-wheeler" style="display: none;"></div>
                </div>
            </div>
            <div class="tk-form-group"> 
                <label for="task_description">Description</label>
                <textarea id="task_description" name="notes" class="form-control" rows="4"></textarea>
            </div>

            <input type="hidden" id="modal_workspace_gid" name="workspace_gid">
            <input type="hidden" id="modal_project_gid" name="project_gid">
            
            <input type="hidden" id="modal_task_gid" name="task_gid">

            <div class="tk-modal-footer"> 
                <div id="modalError" class="tk-modal-error" style="display: none;"></div> 
                <button type="submit" id="submitTaskBtn" class="tk-btn-primary"> 
                    Submit Task
                </button>
            </div>
        </form>
    </div>
</div>

<div id="editModeToast" class="tk-toast-notification">
    Please select a task to edit.
</div>

@endsection