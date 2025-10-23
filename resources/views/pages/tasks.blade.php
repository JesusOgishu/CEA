@extends('layout.layout')

@section('title', 'User Tasks')

@section('content')
{{-- 🔥 Selector de Proyectos SOLO con proyectos --}}
<div class="projects-container mb-4 d-flex align-items-center" style="margin-bottom: 2rem;">
    <label for="projectSelector" class="mr-3 font-weight-bold">Filtrar por Proyecto:</label>
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

@if(empty($tasks))
  <p>No tasks found.</p>
@else
<div class="prj-cards-container">
  @foreach ($tasks as $task)
  <div class="prj-card">
    <div class="prj-card__header">
      <p class="prj-card__title">{{ $task['name'] ?? 'Untitled Task' }}</p>
      <span class="prj-card__section">{{ $task['section_name'] }}</span>
    </div>

    {{-- Proyectos --}}
    <div class="prj-card__projects">
      <strong>Project(s):</strong>
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

    {{-- Descripción --}}
    <div class="prj-card__description">
      <strong>Description:</strong>
      <p>{{ $task['notes'] ?? 'No description' }}</p>
    </div>

    <div class="prj-card__footer">
      <div>
        <small>Due:</small>
        <strong>{{ $task['due_on'] ?? '—' }}</strong>
      </div>
      <div>
        <small>Updated:</small>
        <strong>{{ $task['updated'] ?? '—' }}</strong>
      </div>
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

@push('styles')
<link rel="stylesheet" href="{{ asset('css/task.css') }}">
@endpush

{{-- 🔥 Script para el selector --}}
@push('scripts')
<script src="{{ asset('js/task_filter.js') }}"></script>
@endpush

@endsection
