<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>@yield('title', 'S2P Boiler Dashboard')</title>

{{-- Shared sidebar/logo/nav styles — single source of truth --}}
<link rel="stylesheet" href="{{ asset('css/dashboard-shared.css') }}">

{{-- Per-page fonts, CDN scripts (Tailwind config, Alpine, Chart.js, Plotly, etc.) --}}
@stack('head')

{{-- Per-page <style> blocks (charts, tables, panels — genuinely page-specific) --}}
@stack('styles')
</head>
<body class="@yield('body-class')">

<div class="layout">
    <aside class="sidebar">
        <div class="logo-box">
            <img src="{{ asset('images/logo.png') }}" alt="S2P Logo">
        </div>
        <nav class="sidebar-nav">
            <a href="{{ route('global-view') }}" class="nav-item {{ request()->routeIs('global-view') ? 'active' : '' }}">GLOBAL VIEW</a>
            <a href="{{ route('tube.mapping') }}" class="nav-item {{ request()->routeIs(['tube.mapping', 'tube-mapping.*']) ? 'active' : '' }}">TUBE MAPPING</a>
            <a href="{{ route('rla-analysis') }}" class="nav-item {{ request()->routeIs('rla-analysis') ? 'active' : '' }}">RLA ANALYSIS</a>
            <a href="{{ route('maintenance') }}" class="nav-item {{ request()->routeIs('maintenance') ? 'active' : '' }}">MAINTENANCE</a>
            <a href="{{ route('input-data.index') }}" class="nav-item {{ request()->routeIs('input-data.*') ? 'active' : '' }}">INPUT DATA</a>
        </nav>
    </aside>

    @yield('content')
</div>

@stack('scripts')
</body>
</html>