@extends('layout.layout')

@section('title', 'Perfil de Usuario Asana')

@section('content')
<div class="user-profile-container">
    <div id="user-info-card" class="user-profile-card">
        <h1 class="user-title">
            <i data-lucide="user"></i> Perfil de Usuario Asana
        </h1>

        <div id="loading-section" class="user-loading-section">
            <div class="loading-spinner"></div>
            <p>Cargando datos de Asana...</p>
        </div>

        <div id="user-details" class="user-details" style="display:none;">
            <div class="user-profile-main-info-grid">
                <div class="user-profile-header-container">
                    <div class="user-profile-header">
                        <img 
                            id="profile-photo" 
                            class="user-profile-photo" 
                            alt="Foto de perfil"
                            onerror="this.onerror=null; this.src='https://placehold.co/100x100/969696/FFFFFF?text=JMC'">
                        <div class="user-info">
                            <h2 id="user-name" class="user-name"></h2>
                            <p id="user-email" class="user-email"></p>
                        </div>
                    </div>

                    <div class="user-about-me-section user-section-card">
                        <div class="section-header">
                            <h3><i data-lucide="book-open"></i> Acerca de Mí</h3>
                        </div>
                        <div class="section-content">
                            <p id="user-about-me" class="user-about-me-text">Cargando...</p>
                        </div>
                    </div>
                </div>
                <div class="user-profile-collaboration-details">
                    <div class="user-workspaces-section user-section-card">
                        <div class="section-header">
                            <h3><i data-lucide="briefcase"></i> Workspaces</h3>
                        </div>
                        <div class="section-content scrollable-section">
                            <div id="workspaces-list" class="user-tag-list"></div>
                        </div>
                    </div>

                    <div class="user-team-section user-section-card">
                        <div class="section-header">
                            <h3><i data-lucide="users"></i> Equipos a los que pertenezco</h3>
                        </div>
                        <div class="section-content scrollable-section">
                            <div id="team-list" class="user-tag-list"></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="user-active-projects-section user-section-card mt-6">
                <div class="section-header sticky-header">
                    <h3><i data-lucide="folder-open"></i> Proyectos Activos Asignados</h3>
                </div>
                <div class="section-content scrollable-section">
                    <ul id="active-projects-list" class="user-projects-list"></ul>
                </div>
            </div>
        </div>

        <div id="error-message" class="user-error-message" style="display:none;">
            <p>No se pudo cargar la información del usuario.</p>
            <p id="error-detail"></p>
            <button onclick="window.location.reload()" class="user-btn-refresh">Reintentar</button>
        </div>
    </div>
</div>
@endsection
