<x-dashboard-layout title="Laporan Penjualan" active="sales-history">

    <div x-data="{
        detailModal: false,
        searchDetailItem: '',
        selectedSale: {},
    
        formatDateTime(dtStr) {
            if (!dtStr) return '-';
    
            let d = new Date(dtStr);
    
            return d.toLocaleDateString('id-ID', {
                day: '2-digit',
                month: 'short',
                year: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
        },
    
        formatNumber(val) {
            let num = parseFloat(val) || 0;
            let rounded = Math.round(num * 10) / 10;
    
            return rounded == Math.floor(rounded) ?
                Math.floor(rounded).toLocaleString('id-ID') :
                rounded.toString().replace('.', ',');
        },
    
        formatRp(val) {
            return 'Rp ' + (parseFloat(val) || 0).toLocaleString('id-ID');
        },
    
        get filteredDetailItems() {
            if (!this.selectedSale || !this.selectedSale.items) {
                return [];
            }
    
            let items = this.selectedSale.items.map(item => ({
                ...item,
    
                product_name: item.product ?
                    item.product.name :
                    '-',
    
                unit_name: item.product && item.product.store_unit ?
                    item.product.store_unit.name :
                    'Kg',
    
                formatted_qty: this.formatNumber(item.qty),
                formatted_price: this.formatRp(item.price),
                formatted_discount: this.formatRp(item.discount),
                formatted_subtotal: this.formatRp(item.subtotal),
            }));
    
            if (!this.searchDetailItem.trim()) {
                return items;
            }
    
            return items.filter(i =>
                i.product_name
                .toLowerCase()
                .includes(this.searchDetailItem.toLowerCase())
            );
        },
    
        async openDetailModal(id) {
            try {
                let res = await fetch('/sales/' + id + '/items');
    
                if (res.ok) {
                    this.selectedSale = await res.json();
                    this.searchDetailItem = '';
                    this.detailModal = true;
                }
            } catch (err) {
                Toast.fire({
                    icon: 'error',
                    title: 'Gagal memuat detail penjualan.'
                });
            }
        }
    }" class="space-y-6">

        <!-- Controls Header -->
        <div
            class="bg-white p-5 rounded-3xl border border-slate-200/80 shadow-sm flex flex-col sm:flex-row items-center justify-between gap-4">

            <div>
                <h1 class="text-base font-black text-slate-900">
                    Laporan Penjualan (Sales Report)
                </h1>

                <p class="text-xs text-slate-400">
                    Pencatatan omzet toko, rekapitulasi transaksi kasir,
                    dan ringkasan metode pembayaran
                </p>
            </div>

            <a href="{{ route('sales.create') }}"
                class="w-full sm:w-auto inline-flex items-center justify-center gap-2 rounded-xl bg-abs-green-600 px-4 py-2.5 text-xs font-bold text-white shadow-lg shadow-abs-green-600/20 hover:bg-abs-green-700 transition active:scale-95">

                <i data-lucide="shopping-bag" class="w-4 h-4"></i>

                <span>Ke Kasir POS</span>
            </a>
        </div>


        <!-- KPI SUMMARY CARDS -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">

            <!-- Total Omzet -->
            <div class="bg-white p-5 rounded-3xl border border-slate-200/80 shadow-sm flex items-center gap-4">

                <div class="p-3 bg-emerald-50 text-emerald-600 rounded-2xl">
                    <i data-lucide="trending-up" class="w-6 h-6"></i>
                </div>

                <div>
                    <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">
                        Total Omzet Bersih
                    </span>

                    <h3 class="text-lg font-black text-slate-900">
                        Rp {{ number_format($totalOmset, 0, ',', '.') }}
                    </h3>
                </div>
            </div>


            <!-- Total Transaksi -->
            <div class="bg-white p-5 rounded-3xl border border-slate-200/80 shadow-sm flex items-center gap-4">

                <div class="p-3 bg-blue-50 text-blue-600 rounded-2xl">
                    <i data-lucide="receipt" class="w-6 h-6"></i>
                </div>

                <div>
                    <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">
                        Total Transaksi
                    </span>

                    <h3 class="text-lg font-black text-slate-900">
                        {{ number_format($totalCount, 0, ',', '.') }} Struk
                    </h3>
                </div>
            </div>


            <!-- Rata-rata -->
            <div class="bg-white p-5 rounded-3xl border border-slate-200/80 shadow-sm flex items-center gap-4">

                <div class="p-3 bg-indigo-50 text-indigo-600 rounded-2xl">
                    <i data-lucide="calculator" class="w-6 h-6"></i>
                </div>

                <div>
                    <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">
                        Rata-Rata Transaksi
                    </span>

                    <h3 class="text-lg font-black text-slate-900">
                        Rp {{ number_format($avgTransaction, 0, ',', '.') }}
                    </h3>
                </div>
            </div>


            <!-- Diskon -->
            <div class="bg-white p-5 rounded-3xl border border-slate-200/80 shadow-sm flex items-center gap-4">

                <div class="p-3 bg-amber-50 text-amber-600 rounded-2xl">
                    <i data-lucide="tag" class="w-6 h-6"></i>
                </div>

                <div>
                    <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">
                        Total Diskon Potongan
                    </span>

                    <h3 class="text-lg font-black text-amber-600">
                        Rp {{ number_format($totalDiscount, 0, ',', '.') }}
                    </h3>
                </div>
            </div>

        </div>


        <!-- METODE PEMBAYARAN -->
        <div class="bg-slate-900 text-white p-5 rounded-3xl shadow-xl space-y-3">

            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">
                Ringkasan Metode Pembayaran
            </span>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">

                <div class="p-3 bg-slate-800 rounded-2xl border border-slate-700/60">
                    <span class="text-[11px] text-slate-400 block font-medium">
                        Tunai (Cash)
                    </span>

                    <span class="text-base font-bold text-emerald-400">
                        Rp {{ number_format($cashOmset, 0, ',', '.') }}
                    </span>
                </div>

                <div class="p-3 bg-slate-800 rounded-2xl border border-slate-700/60">
                    <span class="text-[11px] text-slate-400 block font-medium">
                        QRIS
                    </span>

                    <span class="text-base font-bold text-blue-400">
                        Rp {{ number_format($qrisOmset, 0, ',', '.') }}
                    </span>
                </div>

                <div class="p-3 bg-slate-800 rounded-2xl border border-slate-700/60">
                    <span class="text-[11px] text-slate-400 block font-medium">
                        Transfer Bank
                    </span>

                    <span class="text-base font-bold text-indigo-400">
                        Rp {{ number_format($transferOmset, 0, ',', '.') }}
                    </span>
                </div>

            </div>
        </div>


        <!-- FILTER -->
        <div class="bg-white p-5 rounded-3xl border border-slate-200/80 shadow-sm space-y-3">

            <form action="{{ route('sales.index') }}" method="GET" class="grid grid-cols-1 sm:grid-cols-5 gap-2">

                <div>
                    <input type="text" name="search" value="{{ $search }}" placeholder="Kode Invoice..."
                        class="block w-full rounded-xl border-0 bg-slate-50 py-2 px-3 text-xs text-slate-900 ring-1 ring-inset ring-slate-200 focus:ring-2 focus:ring-abs-green-600">
                </div>

                <div>
                    <select name="store_id"
                        class="block w-full rounded-xl border-0 bg-slate-50 py-2 px-3 text-xs text-slate-900 ring-1 ring-inset ring-slate-200 focus:ring-2 focus:ring-abs-green-600">

                        <option value="">
                            -- Semua Toko --
                        </option>

                        @foreach ($stores as $st)
                            <option value="{{ $st->id }}" {{ $storeId == $st->id ? 'selected' : '' }}>
                                {{ $st->name }}
                            </option>
                        @endforeach

                    </select>
                </div>

                <div>
                    <input type="date" name="start_date" value="{{ $startDate }}"
                        class="block w-full rounded-xl border-0 bg-slate-50 py-2 px-3 text-xs text-slate-900 ring-1 ring-inset ring-slate-200 focus:ring-2 focus:ring-abs-green-600">
                </div>

                <div>
                    <input type="date" name="end_date" value="{{ $endDate }}"
                        class="block w-full rounded-xl border-0 bg-slate-50 py-2 px-3 text-xs text-slate-900 ring-1 ring-inset ring-slate-200 focus:ring-2 focus:ring-abs-green-600">
                </div>

                <div class="flex gap-2">
                    <button type="submit"
                        class="w-full px-3 py-2 bg-slate-800 text-white rounded-xl text-xs font-bold hover:bg-slate-900 transition">

                        Filter Laporan
                    </button>
                </div>

            </form>
        </div>


        <!-- TABEL PENJUALAN -->
        <div class="bg-white rounded-3xl border border-slate-200/80 shadow-sm overflow-hidden">

            <div class="overflow-x-auto">

                <table class="w-full text-left text-xs">

                    <thead
                        class="bg-slate-50 text-slate-500 uppercase text-[10px] font-extrabold tracking-wider border-b border-slate-100">

                        <tr>

                            <th class="py-3.5 px-5">
                                Kode Invoice & Toko
                            </th>

                            <th class="py-3.5 px-5">
                                Operator
                            </th>

                            <th class="py-3.5 px-5">
                                Metode & Pembayaran
                            </th>

                            <th class="py-3.5 px-5">
                                Subtotal & Diskon
                            </th>

                            <th class="py-3.5 px-5">
                                Total Akhir
                            </th>

                            <th class="py-3.5 px-5">
                                Waktu Transaksi
                            </th>

                            <th class="py-3.5 px-5 text-center">
                                Aksi
                            </th>

                        </tr>

                    </thead>


                    <tbody class="divide-y divide-slate-100 text-slate-700">

                        @forelse ($sales as $s)
                            <tr class="hover:bg-slate-50/80 transition">

                                <!-- Invoice & Toko -->
                                <td class="py-3.5 px-5">

                                    <span class="font-black text-slate-900 block text-xs">
                                        {{ $s->code }}
                                    </span>

                                    <span
                                        class="text-[11px] font-medium text-slate-500 block mt-0.5 flex items-center gap-1">

                                        <i data-lucide="store" class="w-3 h-3 text-emerald-600 inline"></i>

                                        <span>
                                            {{ $s->store->name ?? '-' }}
                                        </span>

                                    </span>

                                    @if ($s->note)
                                        <p class="text-[10px] text-slate-400 italic mt-0.5 truncate max-w-xs">
                                            {{ $s->note }}
                                        </p>
                                    @endif

                                </td>


                                <!-- OPERATOR -->
                                <td class="py-3.5 px-5">

                                    <div class="flex items-center gap-2">

                                        <div
                                            class="w-7 h-7 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0">

                                            <i data-lucide="user-round" class="w-3.5 h-3.5"></i>

                                        </div>

                                        <div class="min-w-0">

                                            <span class="font-bold text-slate-800 block">
                                                {{ $s->cashier->username ?? '-' }}
                                            </span>

                                            <span class="text-[10px] text-slate-400">
                                                Kasir / Operator
                                            </span>

                                        </div>

                                    </div>

                                </td>


                                <!-- PEMBAYARAN -->
                                <td class="py-3.5 px-5">

                                    <span class="font-extrabold uppercase text-slate-800 block text-xs">
                                        {{ $s->payment_method }}
                                    </span>

                                    <span class="text-[10px] font-medium text-slate-400 block mt-0.5">

                                        Bayar:

                                        <b class="text-slate-700">
                                            Rp {{ number_format($s->paid_amount, 0, ',', '.') }}
                                        </b>

                                        @if ($s->change_amount > 0)
                                            |

                                            Kembalian:

                                            <b class="text-emerald-600">
                                                Rp {{ number_format($s->change_amount, 0, ',', '.') }}
                                            </b>
                                        @endif

                                    </span>

                                </td>


                                <!-- SUBTOTAL -->
                                <td class="py-3.5 px-5">

                                    <span class="text-slate-700 font-bold block">
                                        Rp {{ number_format($s->subtotal, 0, ',', '.') }}
                                    </span>

                                    @if ($s->discount > 0)
                                        <span class="text-[10px] font-bold text-amber-600 block mt-0.5">
                                            Disc: -Rp {{ number_format($s->discount, 0, ',', '.') }}
                                        </span>
                                    @else
                                        <span class="text-[10px] text-slate-400 block mt-0.5">
                                            Tanpa Diskon
                                        </span>
                                    @endif

                                </td>


                                <!-- TOTAL -->
                                <td class="py-3.5 px-5 font-black text-abs-green-700 text-sm">

                                    Rp {{ number_format($s->total_amount, 0, ',', '.') }}

                                </td>


                                <!-- WAKTU -->
                                <td class="py-3.5 px-5 text-slate-600 font-medium">

                                    {{ $s->created_at->format('d M Y, H:i') }}

                                </td>


                                <!-- AKSI -->
                                <td class="py-3.5 px-5 text-center">

                                    <div class="flex items-center justify-center gap-1.5">

                                        <button type="button" @click="openDetailModal({{ $s->id }})"
                                            class="p-2 rounded-xl bg-slate-100 text-slate-700 hover:bg-slate-200 transition"
                                            title="Lihat Detail Item Belanja">

                                            <i data-lucide="eye" class="w-3.5 h-3.5"></i>

                                        </button>


                                        <a href="{{ route('sales.receipt', $s->id) }}" target="_blank"
                                            class="p-2 rounded-xl bg-slate-100 text-slate-700 hover:bg-slate-200 transition"
                                            title="Cetak Struk Thermal">

                                            <i data-lucide="printer" class="w-3.5 h-3.5"></i>

                                        </a>

                                    </div>

                                </td>

                            </tr>

                        @empty

                            <tr>

                                <td colspan="7" class="py-12 text-center text-slate-400">

                                    Data penjualan tidak ditemukan pada periode ini.

                                </td>

                            </tr>
                        @endforelse

                    </tbody>

                </table>

            </div>


            @if ($sales->hasPages())
                <div class="p-4 border-t border-slate-100">
                    {{ $sales->links() }}
                </div>
            @endif

        </div>


        <!-- MODAL DETAIL SALES -->
        <div x-show="detailModal" x-cloak class="relative z-50">

            <!-- Overlay -->
            <div x-show="detailModal" class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm"
                @click="detailModal = false">
            </div>


            <div class="fixed inset-0 z-10 overflow-y-auto">

                <div class="flex min-h-full items-center justify-center p-4">

                    <div
                        class="relative w-full max-w-2xl transform overflow-hidden rounded-3xl bg-white p-6 shadow-2xl transition-all border border-slate-100 space-y-4">

                        <!-- HEADER MODAL -->
                        <div class="flex items-start justify-between border-b border-slate-100 pb-3">

                            <div>

                                <h3 class="text-base font-bold text-slate-900"
                                    x-text="'Rincian Struk (' + (selectedSale.code || '') + ')'">
                                </h3>


                                <div class="mt-1 space-y-1">

                                    <!-- Toko -->
                                    <p class="text-xs text-slate-500 flex items-center gap-1.5">

                                        <i data-lucide="store" class="w-3.5 h-3.5 text-emerald-600"></i>

                                        <span x-text="selectedSale.store ? selectedSale.store.name : '-'">
                                        </span>

                                    </p>


                                  

                                    <!-- Waktu -->
                                    <p class="text-xs text-slate-400 flex items-center gap-1.5">

                                        <i data-lucide="clock" class="w-3.5 h-3.5"></i>

                                        <span x-text="formatDateTime(selectedSale.created_at)">
                                        </span>

                                    </p>

                                </div>

                            </div>


                            <button @click="detailModal = false" type="button"
                                class="text-slate-400 hover:text-slate-600">

                                <i data-lucide="x" class="w-5 h-5"></i>

                            </button>

                        </div>


                        <!-- SEARCH ITEM -->
                        <div>

                            <input type="text" x-model="searchDetailItem"
                                placeholder="Live search produk belanjaan..."
                                class="block w-full rounded-xl border-0 bg-slate-50 py-2 px-3 text-xs text-slate-900 ring-1 ring-inset ring-slate-200 focus:ring-2 focus:ring-abs-green-600">

                        </div>


                        <!-- ITEMS -->
                        <div class="max-h-60 overflow-y-auto border border-slate-100 rounded-2xl">

                            <table class="w-full text-left text-xs">

                                <thead
                                    class="bg-slate-50 text-slate-500 uppercase text-[10px] font-extrabold border-b border-slate-100">

                                    <tr>

                                        <th class="py-2.5 px-3">
                                            Produk
                                        </th>

                                        <th class="py-2.5 px-3">
                                            Qty
                                        </th>

                                        <th class="py-2.5 px-3">
                                            Harga Satuan
                                        </th>

                                        <th class="py-2.5 px-3">
                                            Diskon
                                        </th>

                                        <th class="py-2.5 px-3">
                                            Subtotal
                                        </th>

                                    </tr>

                                </thead>


                                <tbody class="divide-y divide-slate-100 text-slate-700">

                                    <template x-for="item in filteredDetailItems" :key="item.id">

                                        <tr class="hover:bg-slate-50">

                                            <td class="py-2.5 px-3 font-bold text-slate-900"
                                                x-text="item.product_name">
                                            </td>

                                            <td class="py-2.5 px-3 font-bold text-slate-800"
                                                x-text="item.formatted_qty + ' ' + item.unit_name">
                                            </td>

                                            <td class="py-2.5 px-3 text-slate-600"
                                                x-text="item.formatted_price + ' / ' + item.unit_name">
                                            </td>

                                            <td class="py-2.5 px-3 font-bold text-amber-600"
                                                x-text="item.discount > 0 ? '-' + item.formatted_discount : '-'">
                                            </td>

                                            <td class="py-2.5 px-3 font-extrabold text-abs-green-700"
                                                x-text="item.formatted_subtotal">
                                            </td>

                                        </tr>

                                    </template>

                                </tbody>

                            </table>

                        </div>


                        <!-- TOTAL -->
                        <div class="pt-2 flex items-center justify-between border-t border-slate-100">

                            <div>

                                <span class="text-xs text-slate-400 font-bold">
                                    Total Pembayaran:
                                </span>

                                <p class="text-base font-black text-abs-green-700"
                                    x-text="formatRp(selectedSale.total_amount)">
                                </p>

                            </div>


                            <button type="button" @click="detailModal = false"
                                class="rounded-xl px-4 py-2.5 text-xs font-bold text-slate-600 hover:bg-slate-100">

                                Tutup

                            </button>

                        </div>

                    </div>

                </div>

            </div>

        </div>

    </div>

</x-dashboard-layout>
