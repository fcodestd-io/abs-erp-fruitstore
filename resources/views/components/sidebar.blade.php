@props(['active' => 'dashboard'])

@php
    $user = auth()->user();
    $role = $user->role ?? 'admin';
@endphp

<!-- Overlay Mobile -->
<div x-show="sidebarOpen" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0" @click="sidebarOpen = false"
    class="fixed inset-0 z-40 bg-slate-900/50 backdrop-blur-sm lg:hidden" x-cloak></div>

<!-- Sidebar Container -->
<aside :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'"
    class="fixed top-0 left-0 z-50 h-screen w-64 bg-white border-r border-slate-200/80 flex flex-col transition-transform duration-300 ease-in-out">
    <!-- Brand Logo Header -->
    <div class="h-16 flex items-center px-6 border-b border-slate-100">
        <a href="{{ route('dashboard') }}" class="flex items-center gap-2.5">
            <span class="text-lg font-black tracking-tight leading-none">
                <span class="text-abs-green-600">ALAM</span> <span class="text-abs-orange-600">BUAH SEGAR</span>
            </span>
        </a>
    </div>

    <!-- Navigation Menu Items -->
    <div class="flex-1 overflow-y-auto px-4 py-6 space-y-6">

        <!-- Menu Utama -->
        <div>
            <p class="px-3 text-[10px] font-extrabold text-slate-400 uppercase tracking-wider mb-2">Utama</p>
            <nav class="space-y-1">
                <a href="{{ route('dashboard') }}"
                    class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-bold transition {{ request()->routeIs('dashboard') ? 'bg-abs-green-50 text-abs-green-700' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}">
                    <i data-lucide="layout-dashboard"
                        class="w-4 h-4 {{ request()->routeIs('dashboard') ? 'text-abs-green-600' : 'text-slate-400' }}"></i>
                    <span>Dashboard</span>
                </a>
            </nav>
        </div>

        <!-- Role: Owner & Admin (Master Data) -->
        @if (in_array($role, ['owner', 'admin']))
            <div>
                <p class="px-3 text-[10px] font-extrabold text-slate-400 uppercase tracking-wider mb-2">Master Data</p>
                <nav class="space-y-1">
                    <!-- Menu Produk (Termasuk Satuan) -->
                    <a href="{{ Route::has('products.index') ? route('products.index') : route('dashboard') }}"
                        class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-bold transition {{ request()->routeIs('products.*') ? 'bg-abs-green-50 text-abs-green-700' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}">
                        <i data-lucide="package"
                            class="w-4 h-4 {{ request()->routeIs('products.*') ? 'text-abs-green-600' : 'text-slate-400' }}"></i>
                        <span>Produk</span>
                    </a>

                    <!-- Menu Gudang -->
                    <a href="{{ Route::has('warehouses.index') ? route('warehouses.index') : route('dashboard') }}"
                        class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-bold transition {{ request()->routeIs('warehouses.*') ? 'bg-abs-green-50 text-abs-green-700' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}">
                        <i data-lucide="warehouse"
                            class="w-4 h-4 {{ request()->routeIs('warehouses.*') ? 'text-abs-green-600' : 'text-slate-400' }}"></i>
                        <span>Gudang</span>
                    </a>

                    <!-- Menu Toko -->
                    <a href="{{ Route::has('stores.index') ? route('stores.index') : route('dashboard') }}"
                        class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-bold transition {{ request()->routeIs('stores.*') ? 'bg-abs-green-50 text-abs-green-700' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}">
                        <i data-lucide="store"
                            class="w-4 h-4 {{ request()->routeIs('stores.*') ? 'text-abs-green-600' : 'text-slate-400' }}"></i>
                        <span>Toko</span>
                    </a>

                    <!-- Menu Supplier -->
                    <a href="{{ route('suppliers.index') }}"
                        class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-bold transition {{ request()->routeIs('suppliers.*') ? 'bg-abs-green-50 text-abs-green-700' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}">
                        <i data-lucide="truck"
                            class="w-4 h-4 {{ request()->routeIs('suppliers.*') ? 'text-abs-green-600' : 'text-slate-400' }}"></i>
                        <span>Supplier</span>
                    </a>

                    <!-- Menu Pengguna / User -->
                    <a href="{{ Route::has('users.index') ? route('users.index') : route('dashboard') }}"
                        class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-bold transition {{ request()->routeIs('users.*') ? 'bg-abs-green-50 text-abs-green-700' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}">
                        <i data-lucide="users"
                            class="w-4 h-4 {{ request()->routeIs('users.*') ? 'text-abs-green-600' : 'text-slate-400' }}"></i>
                        <span>Pengguna</span>
                    </a>
                </nav>
            </div>
        @endif

        <!-- Role: Warehouse Supervisor / Admin / Owner (Logistik & Gudang) -->
        @if (in_array($role, ['owner', 'admin', 'warehouse_supervisor']))
            <div>
                <p class="px-3 text-[10px] font-extrabold text-slate-400 uppercase tracking-wider mb-2">Inventaris &
                    Gudang</p>
                <nav class="space-y-1">
                    <a href="{{ Route::has('purchase-orders.index') ? route('purchase-orders.index') : route('dashboard') }}"
                        class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-bold transition {{ request()->routeIs('purchase-orders.*') ? 'bg-abs-green-50 text-abs-green-700' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}">
                        <i data-lucide="file-check-2"
                            class="w-4 h-4 {{ request()->routeIs('purchase-orders.*') ? 'text-abs-green-600' : 'text-slate-400' }}"></i>
                        <span>Purchase Order (PO)</span>
                    </a>
                    <a href="{{ Route::has('shipments.index') ? route('shipments.index') : route('dashboard') }}"
                        class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-bold transition {{ request()->routeIs('shipments.*') ? 'bg-abs-green-50 text-abs-green-700' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}">
                        <i data-lucide="send"
                            class="w-4 h-4 {{ request()->routeIs('shipments.*') ? 'text-abs-green-600' : 'text-slate-400' }}"></i>
                        <span>Pengiriman (Shipment)</span>
                    </a>
                 
                </nav>
            </div>
        @endif

        <!-- Role: Cashier / Admin / Owner (Penjualan / POS) -->
        @if (in_array($role, ['owner', 'admin', 'cashier']))
            <div>
                <p class="px-3 text-[10px] font-extrabold text-slate-400 uppercase tracking-wider mb-2">Toko & Kasir</p>
                <nav class="space-y-1">
                    <a href="{{ Route::has('sales.index') ? route('sales.index') : route('dashboard') }}"
                        class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-bold transition {{ request()->routeIs('pos.*') ? 'bg-abs-green-50 text-abs-green-700' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}">
                        <i data-lucide="shopping-bag"
                            class="w-4 h-4 {{ request()->routeIs('pos.*') ? 'text-abs-green-600' : 'text-slate-400' }}"></i>
                        <span>Kasir (POS)</span>
                    </a>
                    <a href="{{ Route::has('discounts.index') ? route('discounts.index') : route('dashboard') }}"
                        class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-bold transition {{ request()->routeIs('discounts.*') ? 'bg-abs-green-50 text-abs-green-700' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}">
                        <i data-lucide="tag"
                            class="w-4 h-4 {{ request()->routeIs('discounts.*') ? 'text-abs-green-600' : 'text-slate-400' }}"></i>
                        <span>Diskon Promo</span>
                    </a>
                </nav>
            </div>
        @endif

    </div>

    <!-- User Profile Footer & Role Badge -->
    <div class="p-4 border-t border-slate-100 bg-slate-50/50">
        <div class="flex items-center gap-3">
            <div
                class="w-9 h-9 rounded-xl bg-abs-green-600 text-white flex items-center justify-center font-bold text-xs shadow-md shadow-abs-green-600/20">
                {{ strtoupper(substr($user->username ?? 'U', 0, 2)) }}
            </div>
            <div class="flex-1 min-w-0">
                <p class="text-xs font-bold text-slate-900 truncate">{{ $user->username ?? 'Pengguna' }}</p>
                <span
                    class="inline-block px-2 py-0.5 rounded-md text-[10px] font-bold bg-slate-200 text-slate-700 capitalize">
                    {{ str_replace('_', ' ', $role) }}
                </span>
            </div>
        </div>
    </div>
</aside>
