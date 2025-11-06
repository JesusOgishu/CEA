<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1.0">
    <title>@yield('title', 'Admin Dashboard')</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@100;200;300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Outlined" rel="stylesheet">
    
  
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
  
</head>
<body>
    <div class="grid-container">
        <header class="header">
            <div class="header-left">
                <div class="menu-icon">
                    <span class="material-icons-outlined">menu</span>
                </div>
                
                @auth 
                    <span class="remove-span">Welcome back, {{ auth()->user()->username ?? 'User' }}!</span>
                @endauth
            </div>
            <div class="header-right">
                <span class="material-icons-outlined">notifications</span>
                <span class="material-icons-outlined">email</span>
                <a class="text-primary" href="{{ route('logout') }}"
                   onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                    <span class="material-icons-outlined">logout</span>
                </a>
            </div>
        </header>
        <aside id="sidebar">
            <div class="sidebar-title">
                <div class="sidebar-brand">
                    <span class="material-icons-outlined">api</span> C.E.A
                </div>
            </div>
            <ul class="sidebar-list">
                <li class="sidebar-list-item">
                    <a href="{{ route('dashboard') }}" target="_self">
                        <span class="material-icons-outlined">dashboard</span> Eisenhower
                    </a>
                </li>
                <li class="sidebar-list-item">
                    <a href="{{ route('tasks.page') }}" target="_self">
                        <span class="material-icons-outlined">inventory_2</span> Tasks
                    </a>
                </li>
                <li class="sidebar-list-item">
                    <a href="{{ route('metrics.page') }}" target="_self">
                        <span class="material-icons-outlined">poll</span> Metrics
                    </a>
                </li>
                <li class="sidebar-list-item">
                    <a href="#" target="_self">
                        <span class="material-icons-outlined">group</span> Teammates
                    </a>
                </li>
                <li class="sidebar-list-item">
                    <a style="font-size:12px" href="{{ route('asana.users.index') }}" target="_self"> 
                        <span class="material-icons-outlined">account_circle</span> {{ auth()->user()->username ?? 'User' }}
                    </a>
                </li>
            </ul>
        </aside>
        <main class="main-container">
            @yield('content')
        </main>
    </div>

    {{-- Scripts JS --}}
    <script src="https://cdn.jsdelivr.net/npm/interactjs/dist/interact.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/apexcharts/3.35.3/apexcharts.min.js"></script>
    
    
    <script src="{{ asset('js/app.js') }}"></script>
    <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
        @csrf
    </form>
</body>
</html>
