@extends('layout.layout')

@section('title', 'Admin Dashboard')

@section('content')

<div class="header-filters-container" style="display: flex; gap: 20px; align-items: center; margin-bottom: 20px;">

    <div class="workspaces-container d-flex align-items-center">
        <label for="workspaceSelector" class="mr-3 font-weight-bold" style="margin-right: 10px; margin-bottom: 0;">Filtrar por Espacio de Trabajo:</label>
        <select id="workspaceSelector" class="form-control" style="width: 300px;">
            @forelse ($workspaces as $workspace)
                <option 
                    value="{{ $workspace['gid'] }}" 
                    {{ $workspaceId == $workspace['gid'] ? 'selected' : '' }}
                >
                    {{ $workspace['name'] }}
                </option>
            @empty
                <option value="">No hay Espacios de Trabajo</option>
            @endforelse
        </select>
    </div>

    <div class="projects-container d-flex align-items-center">
        <label for="projectSelector" class="mr-3 font-weight-bold" style="margin-right: 10px; margin-bottom: 0;">Filtrar por Proyecto:</label>
        <select id="projectSelector" class="form-control" style="width: 300px;">
            <option value="" {{ !request('project') ? 'selected' : '' }}>
                Todas mis tareas en este Workspace
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
        <h3 class="font-weight-bold">Tareas Pendientes </h3>
        <ul id="pending-tasks-list" class="task-cards-container">
            @forelse ($tasksByQuadrant['pending'] as $task)
                <li class="task-card"
                    data-task-id="{{ $task['gid'] }}"
                    data-project-gid="{{ $task['project_gid'] }}">
                    <a href="{{ $task['permalink_url'] }}" target="_blank">
                        <p>{{ $task['name'] }}</p>
                    </a>
                    <small>{{ $task['project_name'] }} | <strong>Actualizado:</strong> {{ $task['updated'] }}</small>
                </li>
            @empty
                <li class="p-3 text-muted">No hay tareas pendientes sin clasificar.</li>
            @endforelse
        </ul>
    </div>

    <div class="eisenhower-matrix">
        <div class="matrix-grid">

            @foreach (['do' => 'Hacer ahora (Importante y Urgente)', 'decide' => 'Decidir (Importante, No Urgente)', 'delegate' => 'Delegar (No Importante, Urgente)', 'delete' => 'Eliminar (No Importante, No Urgente)'] as $key => $label)
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
                                <small>{{ $task['project_name'] }} | <strong>Actualizado:</strong> {{ $task['updated'] }}</small>
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