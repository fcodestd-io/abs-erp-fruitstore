<header
    class="h-16 bg-white border-b border-slate-200/80 sticky top-0 z-30 flex items-center justify-between px-4 sm:px-6">

    <!-- Sidebar Toggle (Mobile) & Page Title -->
    <div class="flex items-center gap-3">
        <button type="button" @click="sidebarOpen = !sidebarOpen"
            class="p-2 rounded-xl text-slate-500 hover:bg-slate-100 lg:hidden transition">
            <i data-lucide="menu" class="w-5 h-5"></i>
        </button>

        <h2 class="text-sm sm:text-base font-bold text-slate-800">
            {{ $title ?? 'Dashboard' }}
        </h2>
    </div>

    <!-- Actions & User Profile Dropdown -->
    <div class="flex items-center gap-3">

        <!-- User Dropdown (Alpine.js) -->
        <div x-data="{ dropdownOpen: false }" class="relative">
            <button @click="dropdownOpen = !dropdownOpen" type="button"
                class="flex items-center gap-2.5 p-1.5 rounded-xl hover:bg-slate-50 transition border border-transparent hover:border-slate-200">
                <div
                    class="w-8 h-8 rounded-lg bg-abs-green-100 text-abs-green-700 flex items-center justify-center font-bold text-xs">
                    {{ strtoupper(substr(auth()->user()->username ?? 'U', 0, 2)) }}
                </div>
                <span class="text-xs font-bold text-slate-700 hidden sm:inline-block">
                    {{ auth()->user()->username ?? 'Pengguna' }}
                </span>
                <i data-lucide="chevron-down" class="w-3.5 h-3.5 text-slate-400"></i>
            </button>

            <!-- Dropdown Menu -->
            <div x-show="dropdownOpen" @click.outside="dropdownOpen = false" x-transition:enter="ease-out duration-100"
                x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
                x-transition:leave="ease-in duration-75" x-transition:leave-start="opacity-100 scale-100"
                x-transition:leave-end="opacity-0 scale-95"
                class="absolute right-0 mt-2 w-48 rounded-2xl bg-white p-1.5 shadow-xl ring-1 ring-slate-900/5 focus:outline-none border border-slate-100"
                x-cloak>
                <div class="px-3 py-2 border-b border-slate-100">
                    <p class="text-xs font-bold text-slate-800">{{ auth()->user()->username ?? 'User' }}</p>
                    <p class="text-[10px] text-slate-400 capitalize">
                        {{ str_replace('_', ' ', auth()->user()->role ?? 'Role') }}</p>
                </div>

                <button type="button"
                    @click="
                        dropdownOpen = false;
                        $dispatch('open-confirm', {
                            title: 'Konfirmasi Keluar',
                            message: 'Apakah Anda yakin ingin keluar dari aplikasi?',
                            confirmText: 'Ya, Keluar',
                            variant: 'danger',
                            actionUrl: '{{ route('logout') }}',
                            actionMethod: 'POST'
                        })
                    "
                    class="w-full text-left flex items-center gap-2 px-3 py-2 text-xs font-semibold text-red-600 rounded-xl hover:bg-red-50 transition mt-1">
                    <i data-lucide="log-out" class="w-4 h-4"></i>
                    <span>Keluar</span>
                </button>
            </div>
        </div>

    </div>
</header>
