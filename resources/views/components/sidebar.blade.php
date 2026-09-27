@props(['active' => 'dashboard'])

@php
    $user = auth()->user();
    $role = $user->role ?? 'admin';

    // Ambil ID Gudang & Toko dari session atau property user
    $warehouseId = session('warehouse_id', $user->warehouse_id ?? null);
    $storeId = session('store_id', $user->store_id ?? null);
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

        <!-- Group 1: Menu Utama -->
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

        <!-- Group 2: Master Data -->
        @if (in_array($role, ['owner', 'admin', 'warehouse_supervisor', 'cashier']))
            <div>
                <p class="px-3 text-[10px] font-extrabold text-slate-400 uppercase tracking-wider mb-2">Master Data</p>
                <nav class="space-y-1">

                    <!-- Menu Produk (Semua Role) -->
                    <a href="{{ Route::has('products.index') ? route('products.index') : route('dashboard') }}"
                        class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-bold transition {{ request()->routeIs('products.*') ? 'bg-abs-green-50 text-abs-green-700' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}">
                        <i data-lucide="package"
                            class="w-4 h-4 {{ request()->routeIs('products.*') ? 'text-abs-green-600' : 'text-slate-400' }}"></i>
                        <span>Produk</span>
                    </a>

                    <!-- Menu Gudang: SPV Gudang langsung ke manage Gudang miliknya, Owner/Admin ke Index -->
                    @if (in_array($role, ['owner', 'admin', 'warehouse_supervisor']))
                        @php
                            $whRoute = route('dashboard');
                            if ($role === 'warehouse_supervisor' && $warehouseId && Route::has('warehouses.manage')) {
                                $whRoute = route('warehouses.manage', $warehouseId);
                            } elseif (Route::has('warehouses.index')) {
                                $whRoute = route('warehouses.index');
                            }
                        @endphp
                        <a href="{{ $whRoute }}"
                            class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-bold transition {{ request()->routeIs('warehouses.*') ? 'bg-abs-green-50 text-abs-green-700' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}">
                            <i data-lucide="warehouse"
                                class="w-4 h-4 {{ request()->routeIs('warehouses.*') ? 'text-abs-green-600' : 'text-slate-400' }}"></i>
                            <span>Gudang</span>
                        </a>
                    @endif

                    <!-- Menu Toko: Kasir langsung ke manage Toko miliknya, Owner/Admin ke Index -->
                    @if (in_array($role, ['owner', 'admin', 'cashier']))
                        @php
                            $stRoute = route('dashboard');
                            if ($role === 'cashier' && $storeId && Route::has('stores.manage')) {
                                $stRoute = route('stores.manage', $storeId);
                            } elseif (Route::has('stores.index')) {
                                $stRoute = route('stores.index');
                            }
                        @endphp
                        <a href="{{ $stRoute }}"
                            class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-bold transition {{ request()->routeIs('stores.*') ? 'bg-abs-green-50 text-abs-green-700' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}">
                            <i data-lucide="store"
                                class="w-4 h-4 {{ request()->routeIs('stores.*') ? 'text-abs-green-600' : 'text-slate-400' }}"></i>
                            <span>Toko</span>
                        </a>
                    @endif

                    <!-- Menu Supplier (Owner & Admin) -->
                    @if (in_array($role, ['owner', 'admin']))
                        <a href="{{ Route::has('suppliers.index') ? route('suppliers.index') : route('dashboard') }}"
                            class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-bold transition {{ request()->routeIs('suppliers.*') ? 'bg-abs-green-50 text-abs-green-700' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}">
                            <i data-lucide="truck"
                                class="w-4 h-4 {{ request()->routeIs('suppliers.*') ? 'text-abs-green-600' : 'text-slate-400' }}"></i>
                            <span>Supplier</span>
                        </a>
                    @endif

                    <!-- Menu Pengguna / User (Khusus Owner) -->
                    @if ($role === 'owner')
                        <a href="{{ Route::has('users.index') ? route('users.index') : route('dashboard') }}"
                            class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-bold transition {{ request()->routeIs('users.*') ? 'bg-abs-green-50 text-abs-green-700' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}">
                            <i data-lucide="users"
                                class="w-4 h-4 {{ request()->routeIs('users.*') ? 'text-abs-green-600' : 'text-slate-400' }}"></i>
                            <span>Pengguna</span>
                        </a>
                    @endif

                </nav>
            </div>
        @endif

        <!-- Group 3: Inventaris & Logistik Gudang -->
        @if (in_array($role, ['owner', 'admin', 'warehouse_supervisor', 'cashier']))
            <div>
                <p class="px-3 text-[10px] font-extrabold text-slate-400 uppercase tracking-wider mb-2">Inventaris &
                    Logistik</p>
                <nav class="space-y-1">

                    <!-- Menu Purchase Order (Owner, Admin, SPV Gudang) -->
                    @if (in_array($role, ['owner', 'admin', 'warehouse_supervisor']))
                        <a href="{{ Route::has('purchase-orders.index') ? route('purchase-orders.index') : route('dashboard') }}"
                            class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-bold transition {{ request()->routeIs('purchase-orders.*') ? 'bg-abs-green-50 text-abs-green-700' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}">
                            <i data-lucide="file-check-2"
                                class="w-4 h-4 {{ request()->routeIs('purchase-orders.*') ? 'text-abs-green-600' : 'text-slate-400' }}"></i>
                            <span>Purchase Order (PO)</span>
                        </a>
                    @endif

                    <!-- Menu Shipment (Owner, Admin, SPV Gudang, Cashier) -->
                    <a href="{{ Route::has('shipments.index') ? route('shipments.index') : route('dashboard') }}"
                        class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-bold transition {{ request()->routeIs('shipments.*') ? 'bg-abs-green-50 text-abs-green-700' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}">
                        <i data-lucide="send"
                            class="w-4 h-4 {{ request()->routeIs('shipments.*') ? 'text-abs-green-600' : 'text-slate-400' }}"></i>
                        <span>Pengiriman (Shipment)</span>
                    </a>

                </nav>
            </div>
        @endif

        <!-- Group 4: Penjualan & Promosi Toko -->
        @if (in_array($role, ['owner', 'admin', 'cashier']))
            <div>
                <p class="px-3 text-[10px] font-extrabold text-slate-400 uppercase tracking-wider mb-2">Toko & Penjualan
                </p>
                <nav class="space-y-1">

                    <!-- Menu POS Kasir -->
                    <a href="{{ Route::has('sales.create') ? route('sales.create') : route('dashboard') }}"
                        class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-bold transition {{ request()->routeIs('sales.create') ? 'bg-abs-green-50 text-abs-green-700' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}">
                        <i data-lucide="shopping-bag"
                            class="w-4 h-4 {{ request()->routeIs('sales.create') ? 'text-abs-green-600' : 'text-slate-400' }}"></i>
                        <span>Kasir (POS)</span>
                    </a>

                    <!-- Menu Riwayat Penjualan / Laporan Sales -->
                    <a href="{{ Route::has('sales.index') ? route('sales.index') : route('dashboard') }}"
                        class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-bold transition {{ request()->routeIs('sales.index') ? 'bg-abs-green-50 text-abs-green-700' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}">
                        <i data-lucide="receipt"
                            class="w-4 h-4 {{ request()->routeIs('sales.index') ? 'text-abs-green-600' : 'text-slate-400' }}"></i>
                        <span>Riwayat Penjualan</span>
                    </a>

                    <!-- Menu Diskon Promo (Khusus Owner) -->
                    @if ($role === 'owner')
                        <a href="{{ Route::has('discounts.index') ? route('discounts.index') : route('dashboard') }}"
                            class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-bold transition {{ request()->routeIs('discounts.*') ? 'bg-abs-green-50 text-abs-green-700' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}">
                            <i data-lucide="tag"
                                class="w-4 h-4 {{ request()->routeIs('discounts.*') ? 'text-abs-green-600' : 'text-slate-400' }}"></i>
                            <span>Diskon Promo</span>
                        </a>
                    @endif

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
