@extends('layout.layout')

@section('title', 'Teammates')

@section('content')
<div class="teammates-page-wrapper">

    <div class="filters-container mb-4" style="padding-bottom: 10px">
        <div class="filters-wrapper"> 
            @if(isset($workspaces) && count($workspaces) > 1)
            <div class="workspace-filter filter-item">
                <label for="workspaceSelector" class="font-weight-bold">Workspace:</label>
                <select id="workspaceSelector" class="form-control">
                    @foreach ($workspaces as $ws)
                        <option value="{{ $ws['gid'] }}" {{ $workspaceId == $ws['gid'] ? 'selected' : '' }}>
                            {{ $ws['name'] }}
                        </option>
                    @endforeach
                </select>
            </div>
            @endif
        </div>
    </div>

    @if(empty($teammates))
        <p class="text-muted text-center mt-5">No teammates found in this workspace.</p>
    @else
        <div class="teammates-grid-container">
            @foreach ($teammates as $user)
                @if(empty($user['name'])) @continue @endif
                <div class="teammate-card" 
                     data-user-gid="{{ $user['gid'] }}" 
                     data-user-name="{{ $user['name'] }}">
                    
                    <div class="teammate-card__photo">
                        @if(!empty($user['photo']['image_128x128']))
                            <img src="{{ $user['photo']['image_128x128'] }}" alt="{{ $user['name'] }}">
                        @else
                            <div class="teammate-card__initials">
                                {{ Str::substr($user['name'], 0, 1) }}
                            </div>
                        @endif
                    </div>

                    <div class="teammate-card__info">
                        <h4 class="teammate-card__name">{{ $user['name'] }}</h4>
                        <p class="teammate-card__title">{{ $user['title'] ?? 'Teammate' }}</p>
                        <p class="teammate-card__email">{{ $user['email'] ?? 'No email' }}</p>
                    </div>

                    <div class="teammate-card__action">
                        View Tasks
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>

<div id="teammateTasksModal" class="tk-modal-overlay" style="display: none;">
    <div class="tk-modal-content tk-modal-content--large"> {{-- Modal más grande --}}
        
        <div class="tk-modal-header">
            <h3 id="teammateModalTitle">Loading tasks...</h3>
            <button id="closeTeammateModalBtn" class="tk-modal-close">&times;</button>
        </div>

        <div id="teammateModalBody" class="tk-modal-body">
            <div class="tk-modal-spinner-container">
                <div class="tk-modal-wheeler"></div>
            </div>
        </div>

    </div>
</div>
@endsection