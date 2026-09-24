<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-slate-950">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Schedio') — Enterprise Scheduling & Collaboration Engine</title>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">

    <!-- Styles & Scripts via Vite -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
</head>
<body class="h-full font-sans antialiased text-slate-100 bg-slate-950 selection:bg-indigo-500 selection:text-white overflow-x-hidden" x-data="{ sidebarOpen: false }">

    @auth
        <div class="min-h-full flex flex-col lg:flex-row">
            <!-- Mobile Sidebar Backdrop -->
            <div x-show="sidebarOpen" x-cloak
                @click="sidebarOpen = false"
                x-transition:enter="transition-opacity ease-linear duration-300"
                x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100"
                x-transition:leave="transition-opacity ease-linear duration-300"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
                class="fixed inset-0 bg-slate-950/80 backdrop-blur-sm z-40 lg:hidden">
            </div>

            <!-- Sidebar Navigation -->
            @include('layouts.partials.sidebar')

            <!-- Main Application Content Area -->
            <div class="flex-1 flex flex-col min-w-0 lg:pl-64">
                <!-- Top Navigation Bar -->
                @include('layouts.partials.topnav')

                <!-- Main Content Body -->
                <main class="flex-1 p-4 sm:p-6 lg:p-8 space-y-6">
                    @yield('content')
                </main>
            </div>
        </div>
    @else
        <!-- Ambient Background Glow Elements for Guest Pages -->
        <div class="fixed inset-0 overflow-hidden pointer-events-none -z-10">
            <div class="absolute -top-40 -left-40 w-96 h-96 bg-indigo-600/15 rounded-full blur-3xl"></div>
            <div class="absolute top-1/3 -right-40 w-96 h-96 bg-purple-600/10 rounded-full blur-3xl"></div>
            <div class="absolute -bottom-40 left-1/3 w-96 h-96 bg-blue-600/10 rounded-full blur-3xl"></div>
        </div>
        @yield('content')
    @endauth

    @stack('scripts')
</body>
</html>
