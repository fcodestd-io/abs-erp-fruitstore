<x-dashboard-layout title="Dashboard Kasir Toko" active="dashboard">
    <div class="space-y-6">

        <!-- Banner Toko -->
        <div
            class="bg-white p-6 rounded-3xl border border-slate-200/80 shadow-sm flex flex-col sm:flex-row items-center justify-between gap-4">
            <div class="flex items-center gap-3">
                <div class="p-3 bg-emerald-50 text-emerald-700 rounded-2xl">
                    <i data-lucide="store" class="w-6 h-6"></i>
                </div>
                <div>
                    <span class="text-xs font-bold text-emerald-600 uppercase tracking-widest block">OPERASIONAL
                        KASIR</span>
                    <h1 class="text-lg font-black text-slate-900">{{ $store->name ?? 'Toko Cabang' }}</h1>
                    <p class="text-xs text-slate-400">{{ $store->address ?? '-' }}</p>
                </div>
            </div>

            <a href="{{ route('sales.create') }}"
                class="w-full sm:w-auto px-5 py-3 bg-abs-green-600 hover:bg-abs-green-700 text-white font-black text-xs rounded-2xl shadow-lg shadow-abs-green-600/20 transition active:scale-95 flex items-center justify-center gap-2">
                <i data-lucide="shopping-bag" class="w-4 h-4"></i>
                <span>BUKA KASIR POS</span>
            </a>
        </div>

        <!-- KPI Kasir -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div class="bg-white p-5 rounded-3xl border border-slate-200/80 shadow-sm">
                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Omzet Kasir Hari
                    Ini</span>
                <h3 class="text-xl font-black text-slate-900 mt-1">Rp {{ number_format($omsetToday, 0, ',', '.') }}</h3>
            </div>

            <div class="bg-white p-5 rounded-3xl border border-slate-200/80 shadow-sm">
                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Transaksi Hari
                    Ini</span>
                <h3 class="text-xl font-black text-slate-900 mt-1">{{ $transCountToday }} Struk</h3>
            </div>

            <div class="bg-white p-5 rounded-3xl border border-slate-200/80 shadow-sm">
                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Total Omzet Bulan
                    Ini</span>
                <h3 class="text-xl font-black text-emerald-700 mt-1">Rp {{ number_format($omsetMonth, 0, ',', '.') }}
                </h3>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Shipment Masuk (Pending) -->
            <div class="bg-white p-5 rounded-3xl border border-slate-200/80 shadow-sm space-y-3">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <h3
                        class="text-xs font-bold text-slate-900 uppercase tracking-wider flex items-center gap-1.5 text-amber-600">
                        <i data-lucide="truck" class="w-4 h-4"></i>
                        <span>Pengiriman Masuk (Perlu Diterima)</span>
                    </h3>
                </div>
                <div class="divide-y divide-slate-100">
                    @forelse ($incomingShipments as $shp)
                        <div class="py-3 flex items-center justify-between text-xs">
                            <div>
                                <span class="font-bold text-slate-900 block">{{ $shp->code }}</span>
                                <span class="text-[10px] text-slate-400">Dari: {{ $shp->warehouse->name ?? '-' }}</span>
                            </div>
                            <a href="{{ route('shipments.index') }}"
                                class="px-3 py-1.5 bg-amber-500 hover:bg-amber-600 text-white font-bold text-[11px] rounded-xl transition">
                                Terima Barang
                            </a>
                        </div>
                    @empty
                        <p class="text-xs text-slate-400 py-4 text-center">Tidak ada pengiriman dalam perjalanan.</p>
                    @endforelse
                </div>
            </div>

            <!-- Stok Toko Menipis -->
            <div class="bg-white p-5 rounded-3xl border border-slate-200/80 shadow-sm space-y-3">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wider text-red-600">Stok Toko Menipis
                    </h3>
                </div>
                <div class="divide-y divide-slate-100">
                    @forelse ($lowStocks as $st)
                        <div class="py-2.5 flex items-center justify-between text-xs">
                            <span class="font-bold text-slate-900">{{ $st->product->name ?? '-' }}</span>
                            <span class="font-black text-red-600 bg-red-50 px-2 py-1 rounded-lg">
                                {{ round($st->stock, 1) }} {{ $st->product->storeUnit->name ?? 'Kg' }}
                            </span>
                        </div>
                    @empty
                        <p class="text-xs text-slate-400 py-4 text-center">Stok toko dalam kondisi cukup.</p>
                    @endforelse
                </div>
            </div>
        </div>

    </div>
</x-dashboard-layout>
