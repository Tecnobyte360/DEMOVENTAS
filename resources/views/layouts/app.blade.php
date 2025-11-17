<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" x-data="{ sidebarOpen: false, sidebarExpanded: localStorage.getItem('sidebar-expanded') === 'true' }" x-init="$watch('sidebarExpanded', val => localStorage.setItem('sidebar-expanded', val)); $store.darkMode.init()">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Laravel') }}</title>

    <script>
    document.addEventListener('alpine:init', () => {
        Alpine.store('darkMode', {
            on: false,
            
            init() {
                this.on = localStorage.getItem('darkMode') === 'true';
                this.updateTheme();
            },
            
            toggle() {
                this.on = !this.on;
                localStorage.setItem('darkMode', this.on);
                this.updateTheme();
            },
            
            updateTheme() {
                if (this.on) {
                    document.documentElement.classList.add('dark');
                } else {
                    document.documentElement.classList.remove('dark');
                }
            }
        });
    });
    </script>

    @livewireStyles
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-inter antialiased bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-gray-100 transition-colors duration-200">
    
    <div class="flex h-screen overflow-hidden">
        <!-- Sidebar -->
        <x-app.sidebar variant="v2" />
        
        <!-- Content area -->
        <div class="relative flex flex-col flex-1 overflow-y-auto overflow-x-hidden">
            <!-- Header -->
            <x-app.header />
            
            <!-- Main content -->
            <main class="grow">
                {{ $slot }}
            </main>
        </div>
    </div>

    @livewireScripts
</body>
</html>