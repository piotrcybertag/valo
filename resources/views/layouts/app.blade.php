<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', config('app.name', 'Laravel'))</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />
    <style>
        /* Pasek menu – granatowy z białymi opcjami (zawsze widoczne) */
        .app-navbar { background:#1e3a5f; width:100%; flex-shrink:0; box-shadow:0 2px 8px rgba(0,0,0,.15); }
        .app-navbar .app-navbar-inner { width:80%; margin:0 auto; padding:0 0 0 .25rem; height:3.5rem; display:flex; align-items:center; justify-content:space-between; }
        .app-navbar a { color:#fff; text-decoration:none; font-size:.875rem; padding:.5rem 1rem; border-radius:.25rem; }
        .app-navbar a:hover { background:rgba(255,255,255,.15); }
        .app-navbar .navbar-logout-btn { background:none; border:none; color:#fff; font-size:.875rem; padding:.5rem 1rem; border-radius:.25rem; cursor:pointer; font-family:inherit; }
        .app-navbar .navbar-logout-btn:hover { background:rgba(255,255,255,.15); }
        .app-navbar .app-navbar-brand { display:flex; align-items:center; gap:.35rem; flex-shrink:0; }
        .app-navbar .app-navbar-logo { font-weight:500; display:flex; align-items:center; height:3.5rem; overflow:hidden; padding:0; }
        .app-navbar .app-navbar-logo img { height:5.1rem; width:auto; display:block; margin-top:8px; }
        .app-navbar .app-navbar-logo .navbar-logo-fallback { font-size:.875rem; font-weight:500; }
        .app-navbar .app-navbar-version { color:#fff; font-size:.625rem; line-height:1; font-weight:400; opacity:.9; user-select:none; white-space:nowrap; }
        .app-navbar .app-navbar-links { display:flex; align-items:center; gap:.25rem; }
        .app-navbar .app-navbar-links a.active { font-weight:500; }
        .app-navbar .app-navbar-links a:not(.active) { opacity:.9; }
        .app-navbar .navbar-dropdown { position:relative; }
        .app-navbar .navbar-dropdown .dropdown-menu { display:none; position:absolute; top:100%; left:0; margin-top:0; background:#1e3a5f; min-width:10rem; padding:.25rem 0; border-radius:.25rem; box-shadow:0 4px 12px rgba(0,0,0,.2); z-index:100; }
        .app-navbar .navbar-dropdown:hover .dropdown-menu { display:block; }
        .app-navbar .dropdown-menu a { display:block; padding:.5rem 1rem; white-space:nowrap; border-radius:0; }
        .app-navbar .dropdown-toggle { cursor:default; color:#fff; padding:.5rem 1rem; display:inline-block; font-size:.875rem; }
        .app-navbar .dropdown-toggle:hover { background:rgba(255,255,255,.15); }
        .page-content { width:100%; max-width:56rem; margin:0 auto; padding:1.5rem; text-align:left; align-self:flex-start; }
        .page-content.page-content--wide { max-width:90%; width:90%; }
        .page-header { display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:1rem; margin-bottom:1.5rem; }
        .page-title { font-size:1.5rem; font-weight:600; margin:0; }
        .page-actions { display:flex; align-items:center; gap:.5rem; flex-wrap:wrap; }
        .btn { display:inline-block; padding:.5rem 1rem; font-size:.875rem; font-weight:500; border-radius:.25rem; text-decoration:none; border:1px solid transparent; cursor:pointer; font-family:inherit; }
        .btn-primary { background:#1e3a5f; color:#fff; }
        .btn-primary:hover { background:#16304d; color:#fff; }
        .btn-danger { background:#b91c1c; color:#fff; }
        .btn-danger:hover { background:#991b1b; color:#fff; }
        .btn-outline { background:transparent; color:#1b1b18; border-color:#d1d5db; }
        .btn-outline:hover { background:#f3f4f6; }
        .btn-sm { padding:.25rem .5rem; font-size:.8125rem; }
        .inline-form { display:inline; }
        .data-table { width:100%; border-collapse:collapse; font-size:.875rem; }
        .data-table th, .data-table td { padding:.5rem .75rem; text-align:left; border-bottom:1px solid #e5e7eb; border-right:1px solid #e5e7eb; }
        .data-table th:last-child, .data-table td:last-child { border-right:none; }
        .data-table th { font-weight:600; background:#f9fafb; }
        .data-table .text-right { text-align:right; }
        .data-table tr.raport-pl-pogrubiony td { font-weight:700; }
        .data-table tr.raport-pl-suma-row td { font-size:.7rem; }
        .data-table tr.raport-pl-naglowek-grupy td { background:#f3f4f6; padding-top:1rem; }
        .data-table tr.raport-pl-grupa-ukryta { display:none; }
        .data-table tr.raport-pl-grupa-details td { font-size:.75rem; font-weight:400; }
        .data-table tr.raport-pl-grupa-details td:first-child { padding-left:1.5rem; }
        .raport-pl-oper-result-label { font-weight:400; color:#6b7280; font-size:.875rem; margin-left:.5rem; }
        .raport-pl-expand { display:inline-block; width:1.25rem; cursor:pointer; font-weight:700; color:#1e3a5f; user-select:none; }
        .raport-pl-expand:hover { color:#16304d; }
        .raport-pl-pozycje-row { display:none; }
        .raport-pl-pozycje-row.raport-pl-pozycje-widoczne { display:table-row; }
        .raport-pl-pozycje-cell { padding:0.5rem 1rem 1rem 3rem !important; background:#f9fafb; font-size:.75rem; }
        .raport-pl-pozycje-table { width:100%; border-collapse:collapse; font-size:.75rem; }
        .raport-pl-pozycje-table th, .raport-pl-pozycje-table td { padding:.25rem .5rem; text-align:left; border:none; }
        .raport-pl-pozycje-table th { font-weight:600; color:#6b7280; }
        .col-actions { white-space:nowrap; }
        .col-actions .inline-form { margin-left:.25rem; }
        .btn-icon { display:inline-flex; align-items:center; justify-content:center; width:2rem; height:2rem; padding:0; border:1px solid #1e3a5f; background:#fff; color:#1e3a5f; border-radius:.25rem; text-decoration:none; cursor:pointer; transition:background .15s, color .15s; }
        .btn-icon:hover { background:#1e3a5f; color:#fff; }
        .btn-icon.btn-icon--danger { border-color:#1e3a5f; color:#1e3a5f; }
        .btn-icon.btn-icon--danger:hover { background:#b91c1c; border-color:#b91c1c; color:#fff; }
        .btn-icon svg { width:1rem; height:1rem; }
        .form-card { background:#fff; border:1px solid #e5e7eb; border-radius:.5rem; padding:1.5rem; max-width:28rem; }
        .form-row { margin-bottom:1rem; }
        .form-row label { display:block; font-size:.875rem; font-weight:500; margin-bottom:.25rem; }
        .form-input { width:100%; height:2.25rem; padding:.5rem .75rem; border:1px solid #d1d5db; border-radius:.25rem; font-size:.875rem; box-sizing:border-box; }
        select.form-input { min-height:2.25rem; }
        .form-error { font-size:.8125rem; color:#b91c1c; display:block; margin-top:.25rem; }
        .form-actions { display:flex; gap:.5rem; margin-top:1.25rem; }
        .alert-success { padding:.75rem 1rem; background:#d1fae5; color:#065f46; border-radius:.25rem; margin-bottom:1rem; font-size:.875rem; }
        .alert-danger { padding:.75rem 1rem; background:#fee2e2; color:#991b1b; border-radius:.25rem; margin-bottom:1rem; font-size:.875rem; }
        .alert-warning { padding:.75rem 1rem; background:#fffbeb; color:#92400e; border:1px solid #d97706; border-radius:.25rem; margin-bottom:1rem; font-size:.875rem; }
        .empty-state { color:#6b7280; font-size:.875rem; }
        .pagination-wrap { margin-top:1rem; }
        .instrukcja-list { padding-left:1.5rem; margin:0 0 1rem; }
        .instrukcja-list li { margin-bottom:1rem; }
        .instrukcja-list ul { margin:.5rem 0 0 1rem; padding-left:1rem; }
        .instrukcja-content p { margin:.5rem 0; font-size:.9375rem; line-height:1.5; color:#374151; }
        .instrukcja-content a { color:#1e3a5f; text-decoration:underline; }
        .instrukcja-content a:hover { color:#16304d; }
        /* Raport P&L – liczby w jednej linii, kolumny miesięcy przewijalne */
        .raport-pl-table th, .raport-pl-table td { white-space:nowrap; }
        .raport-pl-table th:first-child, .raport-pl-table td:first-child { min-width:14rem; }
        .raport-pl-table th:nth-child(n+2), .raport-pl-table td:nth-child(n+2) { min-width:9rem; }
        /* Strona główna */
        .home-hero { text-align:center; max-width:32rem; }
        .home-logo { margin-bottom:2rem; }
        .home-logo-img { height:12rem; width:auto; max-width:100%; display:inline-block; object-fit:contain; }
        .home-logo-text { font-size:10.5rem; font-weight:600; letter-spacing:-0.03em; color:#1b1b18; line-height:1; }
        .dark .home-logo-text { color:#EDEDEC; }
        .home-slogan { background:linear-gradient(135deg,#fafaf9 0%,#f5f5f4 100%); border:1px solid #e8e6e3; border-radius:.75rem; padding:1.75rem 2rem; font-size:1rem; line-height:1.6; color:#52525b; box-shadow:0 4px 6px -1px rgba(0,0,0,.06),0 2px 4px -2px rgba(0,0,0,.04),inset 0 1px 0 rgba(255,255,255,.8); letter-spacing:.01em; }
        .dark .home-slogan { background:linear-gradient(135deg,#1c1c1b 0%,#161615 100%); border-color:#3f3f3c; color:#a1a1aa; box-shadow:0 4px 6px -1px rgba(0,0,0,.2),inset 0 1px 0 rgba(255,255,255,.03); }
        /* Ekran logowania – logo z prawej */
        .login-page .login-layout { display:flex; align-items:center; gap:3rem; max-width:48rem; }
        .login-page .login-form-col { flex:1; min-width:0; }
        .login-page .login-logo-col { flex-shrink:0; display:flex; align-items:center; justify-content:center; }
        .login-page .login-logo { max-height:14rem; width:auto; max-width:20rem; object-fit:contain; }
        .login-page .login-logo-fallback { font-size:6rem; font-weight:600; color:#1e3a5f; letter-spacing:-0.02em; }
        @media (max-width:640px) { .login-page .login-logo-col { display:none; } }
    </style>
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @else
        <style>
            body{font-family:'Instrument Sans',system-ui,sans-serif;margin:0;min-height:100vh;display:flex;flex-direction:column;background:#fdfdfc;}
            main{flex:1;display:flex;align-items:center;justify-content:center;padding:1.5rem;}
            .btn{display:inline-block;padding:.5rem 1.25rem;background:#1b1b18;color:#fff;border-radius:.25rem;font-size:.875rem;font-weight:500;cursor:pointer;border:none;}
        </style>
    @endif
</head>
<body class="bg-[#FDFDFC] dark:bg-[#0a0a0a] text-[#1b1b18] dark:text-[#EDEDEC] min-h-screen flex flex-col">
    <nav class="app-navbar">
        <div class="app-navbar-inner">
            <div class="app-navbar-brand">
                <a href="{{ url('/') }}" class="app-navbar-logo">
                    @if(file_exists(public_path('logo_bez_tła.png')))
                        <img src="{{ asset('logo_bez_tła.png') }}" alt="valo" />
                    @elseif(file_exists(public_path('logo.png')))
                        <img src="{{ asset('logo.png') }}" alt="valo" />
                    @elseif(file_exists(public_path('logo.svg')))
                        <img src="{{ asset('logo.svg') }}" alt="valo" />
                    @else
                        <span class="navbar-logo-fallback">valo</span>
                    @endif
                </a>
                @if(!empty($appVersion))
                    <span class="app-navbar-version" title="Wersja">{{ $appVersion }}</span>
                @endif
            </div>
            <div class="app-navbar-links">
                @guest
                    <a href="{{ route('login') }}" class="{{ request()->routeIs('login') ? 'active' : '' }}">Zaloguj</a>
                @else
                    <div class="navbar-dropdown">
                        <span class="dropdown-toggle {{ request()->routeIs('plan-kont.*') || request()->routeIs('grupy.*') ? 'active' : '' }}">Kartoteki</span>
                        <div class="dropdown-menu">
                            <a href="{{ route('plan-kont.index') }}" class="{{ request()->routeIs('plan-kont.*') ? 'active' : '' }}">Plan kont</a>
                            <a href="{{ route('grupy.index') }}" class="{{ request()->routeIs('grupy.*') ? 'active' : '' }}">Grupy</a>
                        </div>
                    </div>
                    <a href="{{ route('import.index') }}" class="{{ request()->routeIs('import.*') ? 'active' : '' }}">Import</a>
                    <a href="{{ route('piatki.index') }}" class="{{ request()->routeIs('piatki.*') ? 'active' : '' }}">Piątki</a>
                    <a href="{{ route('wip.index') }}" class="{{ request()->routeIs('wip.*') ? 'active' : '' }}">WIP</a>
                    <a href="{{ route('raport-pl.index') }}" class="{{ request()->routeIs('raport-pl.*') ? 'active' : '' }}">Raport P&L</a>
                    <div class="navbar-dropdown">
                        <span class="dropdown-toggle {{ request()->routeIs('plan-roczny.*') || request()->routeIs('import-plan-kont.*') || request()->routeIs('users.*') || request()->routeIs('wip-okno.*') ? 'active' : '' }}">Ustawienia</span>
                        <div class="dropdown-menu">
                            <a href="{{ route('plan-roczny.edit') }}" class="{{ request()->routeIs('plan-roczny.*') ? 'active' : '' }}">Plan roczny</a>
                            <a href="{{ route('wip-okno.edit') }}" class="{{ request()->routeIs('wip-okno.*') ? 'active' : '' }}">Okno WIP</a>
                            <a href="{{ route('import-plan-kont.index') }}" class="{{ request()->routeIs('import-plan-kont.*') ? 'active' : '' }}">Import planu kont</a>
                            @if(strtoupper(trim(auth()->user()->typ ?? '')) === 'ADM')
                            <a href="{{ route('users.index') }}" class="{{ request()->routeIs('users.*') ? 'active' : '' }}">Użytkownicy</a>
                            @endif
                        </div>
                    </div>
                    <a href="{{ route('instrukcja') }}" class="{{ request()->routeIs('instrukcja') ? 'active' : '' }}">Instrukcja</a>
                    <form action="{{ route('logout') }}" method="POST" class="inline-form">
                        @csrf
                        <button type="submit" class="navbar-logout-btn">Wyloguj</button>
                    </form>
                @endguest
            </div>
        </div>
    </nav>
    <main class="flex-1 flex items-center justify-center px-6">
        @yield('content')
    </main>
</body>
</html>
