<x-dashboard-layout title="Dashboard Supervisor Gudang" active="dashboard">
    <div class="space-y-6">

        <!-- Banner Gudang -->
        <div class="bg-white p-6 rounded-3xl border border-slate-200/80 shadow-sm flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="p-3 bg-blue-50 text-blue-700 rounded-2xl">
                    <i data-lucide="warehouse" class="w-6 h-6"></i>
                </div>
                <div>
                    <span class="text-xs font-bold text-blue-600 uppercase tracking-widest block">OPERASIONAL
                        GUDANG</span>
                    <h1 class="text-lg font-black text-slate-900">{{ $warehouse->name ?? 'Gudang Utama' }}</h1>
                    <p class="text-xs text-slate-400">{{ $warehouse->address ?? '-' }}</p>
                </div>
            </div>

            <a href="{{ route('purchase-orders.create') }}"
                class="px-4 py-2.5 bg-abs-green-600 hover:bg-abs-green-700 text-white font-bold text-xs rounded-xl shadow-lg transition active:scale-95 flex items-center gap-2">
                <i data-lucide="plus" class="w-4 h-4"></i>
                <span>Buat PO Baru</span>
            </a>
        </div>

        <!-- KPI Gudang -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div class="bg-white p-5 rounded-3xl border border-slate-200/80 shadow-sm">
                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">PO Menunggu
                    Datang</span>
                <h3 class="text-xl font-black text-amber-600 mt-1">{{ $pendingPO }} Transaksi</h3>
            </div>

            <div class="bg-white p-5 rounded-3xl border border-slate-200/80 shadow-sm">
                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Shipment Dikirim Ke
                    Toko</span>
                <h3 class="text-xl font-black text-purple-600 mt-1">{{ $pendingShipment }} Surat Jalan</h3>
            </div>

            <div class="bg-white p-5 rounded-3xl border border-slate-200/80 shadow-sm">
                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Total Variasi Produk
                    (SKU)</span>
                <h3 class="text-xl font-black text-slate-900 mt-1">{{ $totalItemsSKU }} Jenis Barang</h3>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Incoming PO Menunggu Konfirmasi Gudang -->
            <div class="bg-white p-5 rounded-3xl border border-slate-200/80 shadow-sm space-y-3">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wider text-amber-600">PO Datang
                        (Perlu Periksa Fisik)</h3>
                    <a href="{{ route('purchase-orders.index') }}"
                        class="text-[11px] font-bold text-abs-green-600 hover:underline">Lihat Semua PO</a>
                </div>
                <div class="divide-y divide-slate-100">
                    @forelse ($recentPOs as $po)
                        <div class="py-3 flex items-center justify-between text-xs">
                            <div>
                                <span class="font-bold text-slate-900 block">{{ $po->po_code }}</span>
                                <span class="text-[10px] text-slate-400">Supplier:
                                    {{ $po->supplier->name ?? 'Non-Supplier' }}</span>
                            </div>
                            <a href="{{ route('purchase-orders.index') }}"
                                class="px-3 py-1.5 bg-amber-500 hover:bg-amber-600 text-white font-bold text-[11px] rounded-xl transition">
                                Proses Datang
                            </a>
                        </div>
                    @empty
                        <p class="text-xs text-slate-400 py-4 text-center">Belum ada PO masuk yang pending.</p>
                    @endforelse
                </div>
            </div>

            <!-- Stok Gudang Menipis -->
            <div class="bg-white p-5 rounded-3xl border border-slate-200/80 shadow-sm space-y-3">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wider text-red-600">Stok Grosir
                        Gudang Menipis</h3>
                </div>
                <div class="divide-y divide-slate-100">
                    @forelse ($lowStocks as $st)
                        <div class="py-2.5 flex items-center justify-between text-xs">
                            <span class="font-bold text-slate-900">{{ $st->product->name ?? '-' }}</span>
                            <span class="font-black text-red-600 bg-red-50 px-2 py-1 rounded-lg">
                                {{ round($st->stock, 1) }} {{ $st->product->warehouseUnit->name ?? 'Dus' }}
                            </span>
                        </div>
                    @empty
                        <p class="text-xs text-slate-400 py-4 text-center">Stok gudang dalam keadaan aman.</p>
                    @endforelse
                </div>
            </div>
        </div>

    </div>
</x-dashboard-layout>
