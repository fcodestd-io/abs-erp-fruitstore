@props(['title' => 'Dashboard', 'active' => 'dashboard'])

<x-app-layout :title="$title">
    <div x-data="{ sidebarOpen: false }" class="min-h-screen bg-slate-50 flex">

        <!-- Sidebar Component -->
        <x-sidebar :active="$active" />

        <!-- Main Content Area -->
        <div class="flex-1 lg:pl-64 flex flex-col min-w-0">

            <!-- Navbar Component -->
            <x-navbar :title="$title" />

            <!-- Dynamic Content Slot -->
            <main class="flex-1 p-4 sm:p-6 lg:p-8">
                {{ $slot }}
            </main>

        </div>

        <!-- Global Confirmation Dialog Component -->
        <x-confirm-dialog />

    </div>
</x-app-layout>
