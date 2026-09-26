<x-dashboard-layout title="Pembelian Supplier (PO)" active="purchase-orders">
    <div x-data="{
        detailModal: false,
        receiveModal: false,
        searchDetailItem: '',
        searchReceiveItem: '',
        selectedPo: {},
    
        formatDateTime(dtStr) {
            if (!dtStr) return '-';
            let d = new Date(dtStr);
            return d.toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' });
        },
    
        formatNumber(val) {
            let num = parseFloat(val) || 0;
            return Math.round(num * 10) / 10 == Math.floor(num) ?
                Math.floor(num).toLocaleString('id-ID') :
                (Math.round(num * 10) / 10).toLocaleString('id-ID');
        },
    
        formatRp(val) {
            return 'Rp ' + (parseFloat(val) || 0).toLocaleString('id-ID');
        },
    
        get filteredDetailItems() {
            if (!this.selectedPo || !this.selectedPo.items) return [];
            let items = this.selectedPo.items.map(item => ({
                ...item,
                unit_name: item.product && item.product.warehouse_unit ? item.product.warehouse_unit.name : (item.product && item.product.store_unit ? item.product.store_unit.name : 'Dus'),
                formatted_qty_ordered: this.formatNumber(item.qty_ordered),
                formatted_qty_actual: this.formatNumber(item.qty_actual),
                formatted_price: this.formatRp(item.price),
                formatted_subtotal: this.formatRp(item.subtotal),
            }));
    
            if (!this.searchDetailItem.trim()) return items;
            return items.filter(i => i.product && i.product.name.toLowerCase().includes(this.searchDetailItem.toLowerCase()));
        },
    
        get filteredReceiveItems() {
            if (!this.selectedPo || !this.selectedPo.items) return [];
    
            let items = this.selectedPo.items.map(item => {
                let rawQtyActual = item.qty_actual > 0 ? item.qty_actual : item.qty_ordered;
                let numActual = parseFloat(rawQtyActual) || 0;
                let actualVal = Math.round(numActual * 10) / 10 == Math.floor(numActual) ?
                    Math.floor(numActual) :
                    Math.round(numActual * 10) / 10;
    
                let numOrdered = parseFloat(item.qty_ordered) || 0;
                let orderedVal = Math.round(numOrdered * 10) / 10 == Math.floor(numOrdered) ?
                    Math.floor(numOrdered) :
                    Math.round(numOrdered * 10) / 10;
    
                return {
                    ...item,
                    product_name: item.product ? item.product.name : '-',
                    unit_name: item.product && item.product.warehouse_unit ? item.product.warehouse_unit.name : (item.product && item.product.store_unit ? item.product.store_unit.name : 'Dus'),
                    formatted_qty_ordered: orderedVal,
                    actual_val: actualVal,
                    raw_price: parseFloat(item.price) || 0
                };
            });
    
            if (!this.searchReceiveItem.trim()) return items;
            return items.filter(i => i.product_name.toLowerCase().includes(this.searchReceiveItem.toLowerCase()));
        },
    
        async openDetailModal(poId) {
            try {
                let res = await fetch('/purchase-orders/' + poId + '/items');
                if (res.ok) {
                    this.selectedPo = await res.json();
                    this.searchDetailItem = '';
                    this.detailModal = true;
                }
            } catch (err) { Toast.fire({ icon: 'error', title: 'Gagal memuat rincian PO.' }); }
        },
    
        async openReceiveModal(poId) {
            try {
                let res = await fetch('/purchase-orders/' + poId + '/items');
                if (res.ok) {
                    this.selectedPo = await res.json();
                    this.searchReceiveItem = '';
                    this.receiveModal = true;
                }
            } catch (err) { Toast.fire({ icon: 'error', title: 'Gagal memuat data PO.' }); }
        }
    }" class="space-y-6">

        <!-- Controls Header -->
        <div
            class="bg-white p-5 rounded-3xl border border-slate-200/80 shadow-sm flex flex-col sm:flex-row items-center justify-between gap-4">
            <div>
                <h1 class="text-base font-black text-slate-900">Pembelian Supplier (Purchase Orders)</h1>
                <p class="text-xs text-slate-400">Pencatatan PO dan proses konfirmasi barang masuk ke gudang</p>
            </div>

            <a href="{{ route('purchase-orders.create') }}"
                class="w-full sm:w-auto inline-flex items-center justify-center gap-2 rounded-xl bg-abs-green-600 px-4 py-2.5 text-xs font-bold text-white shadow-lg shadow-abs-green-600/20 hover:bg-abs-green-700 transition active:scale-95">
                <i data-lucide="plus" class="w-4 h-4"></i>
                <span>Buat PO Baru</span>
            </a>
        </div>

        <!-- Filter Server Side -->
        <div class="bg-white p-5 rounded-3xl border border-slate-200/80 shadow-sm space-y-3">
            <form action="{{ route('purchase-orders.index') }}" method="GET"
                class="grid grid-cols-1 sm:grid-cols-5 gap-2">
                <div>
                    <input type="text" name="search" value="{{ $search }}"
                        placeholder="Kode PO / Supplier / Note..."
                        class="block w-full rounded-xl border-0 bg-slate-50 py-2 px-3 text-xs text-slate-900 ring-1 ring-inset ring-slate-200 focus:ring-2 focus:ring-abs-green-600">
                </div>

                <div>
                    <select name="status"
                        class="block w-full rounded-xl border-0 bg-slate-50 py-2 px-3 text-xs text-slate-900 ring-1 ring-inset ring-slate-200 focus:ring-2 focus:ring-abs-green-600">
                        <option value="">-- Semua Status --</option>
                        <option value="pending" {{ $status === 'pending' ? 'selected' : '' }}>PENDING (MENUNGGU)
                        </option>
                        <option value="completed" {{ $status === 'completed' ? 'selected' : '' }}>COMPLETED (SELESAI)
                        </option>
                        <option value="canceled" {{ $status === 'canceled' ? 'selected' : '' }}>CANCELED (BATAL)
                        </option>
                    </select>
                </div>

                <div>
                    <select name="supplier_id"
                        class="block w-full rounded-xl border-0 bg-slate-50 py-2 px-3 text-xs text-slate-900 ring-1 ring-inset ring-slate-200 focus:ring-2 focus:ring-abs-green-600">
                        <option value="">-- Semua Supplier --</option>
                        @foreach ($suppliers as $sup)
                            <option value="{{ $sup->id }}" {{ $supplierId == $sup->id ? 'selected' : '' }}>
                                {{ $sup->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <select name="month"
                        class="block w-full rounded-xl border-0 bg-slate-50 py-2 px-3 text-xs text-slate-900 ring-1 ring-inset ring-slate-200 focus:ring-2 focus:ring-abs-green-600">
                        <option value="">-- Semua Bulan --</option>
                        @for ($m = 1; $m <= 12; $m++)
                            <option value="{{ $m }}" {{ $month == $m ? 'selected' : '' }}>
                                {{ DateTime::createFromFormat('!m', $m)->format('F') }}</option>
                        @endfor
                    </select>
                </div>

                <div class="flex gap-2">
                    <select name="year"
                        class="block w-full rounded-xl border-0 bg-slate-50 py-2 px-3 text-xs text-slate-900 ring-1 ring-inset ring-slate-200 focus:ring-2 focus:ring-abs-green-600">
                        <option value="">-- Tahun --</option>
                        @for ($y = date('Y'); $y >= date('Y') - 3; $y--)
                            <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>
                                {{ $y }}</option>
                        @endfor
                    </select>
                    <button type="submit"
                        class="px-3 py-2 bg-slate-800 text-white rounded-xl text-xs font-bold hover:bg-slate-900 transition">Filter</button>
                </div>
            </form>
        </div>

        <!-- Tabel Riwayat PO Ringkas & Rapi -->
        <div class="bg-white rounded-3xl border border-slate-200/80 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead
                        class="bg-slate-50 text-slate-500 uppercase text-[10px] font-extrabold tracking-wider border-b border-slate-100">
                        <tr>
                            <th class="py-3.5 px-5">Kode PO & Supplier</th>
                            <th class="py-3.5 px-5">Gudang & Operator</th>
                            <th class="py-3.5 px-5">Total Nilai</th>
                            <th class="py-3.5 px-5">Status</th>
                            <th class="py-3.5 px-5">Waktu Transaksi</th>
                            <th class="py-3.5 px-5 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-slate-700">
                        @forelse ($purchaseOrders as $po)
                            <tr class="hover:bg-slate-50/80 transition">
                                <!-- Kode PO & Supplier -->
                                <td class="py-3.5 px-5">
                                    <span class="font-black text-slate-900 block text-xs">{{ $po->po_code }}</span>
                                    <span
                                        class="text-[11px] font-medium text-slate-500 block mt-0.5 flex items-center gap-1">
                                        <i data-lucide="truck" class="w-3 h-3 text-slate-400 inline"></i>
                                        <span>{{ $po->supplier->name ?? 'Non-Supplier' }}</span>
                                    </span>
                                    @if ($po->note)
                                        <p class="text-[10px] text-slate-400 italic mt-0.5 truncate max-w-xs">
                                            {{ $po->note }}</p>
                                    @endif
                                </td>

                                <!-- Gudang & Operator -->
                                <td class="py-3.5 px-5">
                                    <span
                                        class="font-bold text-slate-800 block">{{ $po->warehouse->name ?? '-' }}</span>
                                    <span class="text-[11px] font-medium text-slate-400 block mt-0.5">
                                        Oleh: <b class="text-slate-600">{{ $po->operator->username ?? '-' }}</b>
                                    </span>
                                </td>

                                <!-- Total Nilai PO -->
                                <td class="py-3.5 px-5 font-black text-abs-green-700 text-sm">
                                    Rp {{ number_format($po->total_amount, 0, ',', '.') }}
                                </td>

                                <!-- Status Badge -->
                                <td class="py-3.5 px-5">
                                    @if ($po->status === 'pending')
                                        <span
                                            class="inline-flex items-center px-2.5 py-1 rounded-lg bg-amber-100 text-amber-800 font-extrabold text-[10px] uppercase">
                                            PENDING
                                        </span>
                                    @elseif($po->status === 'completed')
                                        <span
                                            class="inline-flex items-center px-2.5 py-1 rounded-lg bg-emerald-100 text-emerald-800 font-extrabold text-[10px] uppercase">
                                            COMPLETED
                                        </span>
                                    @else
                                        <span
                                            class="inline-flex items-center px-2.5 py-1 rounded-lg bg-red-100 text-red-800 font-extrabold text-[10px] uppercase">
                                            CANCELED
                                        </span>
                                    @endif
                                </td>

                                <!-- Waktu Transaksi -->
                                <td class="py-3.5 px-5">
                                    <span
                                        class="text-slate-700 font-medium block">{{ $po->created_at->format('d M Y, H:i') }}</span>
                                    @if ($po->completed_at)
                                        <span class="text-[10px] font-bold text-emerald-600 block mt-0.5">
                                            Selesai: {{ $po->completed_at->format('d M Y, H:i') }}
                                        </span>
                                    @elseif($po->canceled_at)
                                        <span class="text-[10px] font-bold text-red-500 block mt-0.5">
                                            Batal: {{ $po->canceled_at->format('d M Y, H:i') }}
                                        </span>
                                    @endif
                                </td>

                                <!-- Aksi -->
                                <td class="py-3.5 px-5 text-center">
                                    <div class="flex items-center justify-center gap-1.5">
                                        <button type="button" @click="openDetailModal({{ $po->id }})"
                                            class="p-2 rounded-xl bg-slate-100 text-slate-700 hover:bg-slate-200 transition"
                                            title="Lihat Detail Items">
                                            <i data-lucide="eye" class="w-3.5 h-3.5"></i>
                                        </button>

                                        @if ($po->status === 'pending')
                                            <button type="button" @click="openReceiveModal({{ $po->id }})"
                                                class="px-3 py-1.5 rounded-xl bg-amber-500 text-white hover:bg-amber-600 font-bold text-[11px] shadow-sm transition active:scale-95">
                                                Proses Datang
                                            </button>

                                            <button type="button"
                                                @click="$dispatch('open-confirm', {
                                                    title: 'Batalkan PO',
                                                    message: 'Apakah Anda yakin ingin membatalkan PO {{ $po->po_code }}?',
                                                    confirmText: 'Ya, Batalkan',
                                                    variant: 'danger',
                                                    actionUrl: '{{ route('purchase-orders.cancel', $po->id) }}',
                                                    actionMethod: 'POST'
                                                })"
                                                class="px-2.5 py-1.5 rounded-xl bg-red-50 text-red-600 hover:bg-red-100 font-bold text-[11px] transition">
                                                Batalkan
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="py-12 text-center text-slate-400">Data Purchase Order tidak
                                    ditemukan.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($purchaseOrders->hasPages())
                <div class="p-4 border-t border-slate-100">{{ $purchaseOrders->links() }}</div>
            @endif
        </div>

        <!-- MODAL 1: DETAIL ITEMS PO -->
        <div x-show="detailModal" x-cloak class="relative z-50">
            <div x-show="detailModal" class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm"
                @click="detailModal = false"></div>
            <div class="fixed inset-0 z-10 overflow-y-auto">
                <div class="flex min-h-full items-center justify-center p-4">
                    <div
                        class="relative w-full max-w-2xl transform overflow-hidden rounded-3xl bg-white p-6 shadow-2xl transition-all border border-slate-100 space-y-4">
                        <div class="flex items-start justify-between border-b border-slate-100 pb-4">
                            <div>
                                <h3 class="text-base font-bold text-slate-900"
                                    x-text="'Detail PO (' + (selectedPo.po_code || '') + ')'"></h3>
                                <p class="text-xs text-slate-500"
                                    x-text="'Supplier: ' + (selectedPo.supplier ? selectedPo.supplier.name : 'Non-Supplier')">
                                </p>
                            </div>
                            <button @click="detailModal = false" type="button"
                                class="text-slate-400 hover:text-slate-600"><i data-lucide="x"
                                    class="w-5 h-5"></i></button>
                        </div>

                        <div>
                            <input type="text" x-model="searchDetailItem" placeholder="search barang di detail..."
                                class="block w-full rounded-xl border-0 bg-slate-50 py-2 px-3 text-xs text-slate-900 ring-1 ring-inset ring-slate-200 focus:ring-2 focus:ring-abs-green-600">
                        </div>

                        <div class="max-h-60 overflow-y-auto border border-slate-100 rounded-2xl">
                            <table class="w-full text-left text-xs">
                                <thead
                                    class="bg-slate-50 text-slate-500 uppercase text-[10px] font-extrabold border-b border-slate-100">
                                    <tr>
                                        <th class="py-2.5 px-3">Produk</th>
                                        <th class="py-2.5 px-3">Qty Dipesan</th>
                                        <th class="py-2.5 px-3">Qty Datang</th>
                                        <th class="py-2.5 px-3">Harga</th>
                                        <th class="py-2.5 px-3">Subtotal</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 text-slate-700">
                                    <template x-for="item in filteredDetailItems" :key="item.id">
                                        <tr class="hover:bg-slate-50">
                                            <td class="py-2.5 px-3 font-bold text-slate-900"
                                                x-text="item.product ? item.product.name : '-'"></td>
                                            <td class="py-2.5 px-3">
                                                <span x-text="item.formatted_qty_ordered"></span>
                                                <span class="text-[10px] font-bold text-slate-400 ml-0.5"
                                                    x-text="item.unit_name"></span>
                                            </td>
                                            <td class="py-2.5 px-3 font-bold text-slate-900">
                                                <span x-text="item.formatted_qty_actual"></span>
                                                <span class="text-[10px] font-bold text-slate-400 ml-0.5"
                                                    x-text="item.unit_name"></span>
                                            </td>
                                            <td class="py-2.5 px-3" x-text="item.formatted_price"></td>
                                            <td class="py-2.5 px-3 font-bold text-abs-green-700"
                                                x-text="item.formatted_subtotal"></td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>

                        <div class="pt-2 flex items-center justify-end">
                            <button type="button" @click="detailModal = false"
                                class="rounded-xl px-4 py-2.5 text-xs font-bold text-slate-600 hover:bg-slate-100">Tutup</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- MODAL 2: KONFIRMASI BARANG DATANG (HANYA INPUT QTY AKTUAL) -->
        <div x-show="receiveModal" x-cloak class="relative z-50">
            <div x-show="receiveModal" class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm"
                @click="receiveModal = false"></div>
            <div class="fixed inset-0 z-10 overflow-y-auto">
                <div class="flex min-h-full items-center justify-center p-4">
                    <div
                        class="relative w-full max-w-2xl transform overflow-hidden rounded-3xl bg-white p-6 shadow-2xl transition-all border border-slate-100 space-y-4">

                        <!-- Header Modal -->
                        <div class="flex items-start justify-between border-b border-slate-100 pb-3">
                            <div>
                                <h3 class="text-base font-bold text-slate-900"
                                    x-text="'Konfirmasi Barang Datang (' + (selectedPo.po_code || '') + ')'"></h3>
                                <p class="text-xs text-slate-400">Input kuantitas fisik aktual yang sampai di gudang
                                </p>
                            </div>
                            <button @click="receiveModal = false" type="button"
                                class="text-slate-400 hover:text-slate-600">
                                <i data-lucide="x" class="w-5 h-5"></i>
                            </button>
                        </div>

                        <!-- Section Informasi PO -->
                        <div
                            class="grid grid-cols-1 sm:grid-cols-3 gap-2 bg-slate-50 p-3 rounded-2xl border border-slate-100 text-[11px]">
                            <div>
                                <span class="text-slate-400 block font-bold">SUPPLIER</span>
                                <span class="font-bold text-slate-800"
                                    x-text="selectedPo.supplier ? selectedPo.supplier.name : 'Non-Supplier'"></span>
                            </div>
                            <div>
                                <span class="text-slate-400 block font-bold">GUDANG TUJUAN</span>
                                <span class="font-bold text-slate-800"
                                    x-text="selectedPo.warehouse ? selectedPo.warehouse.name : '-'"></span>
                            </div>
                            <div>
                                <span class="text-slate-400 block font-bold">WAKTU ORDER</span>
                                <span class="font-bold text-slate-800"
                                    x-text="formatDateTime(selectedPo.created_at)"></span>
                            </div>
                        </div>

                        <!-- Input Live Search Client-Side -->
                        <div>
                            <input type="text" x-model="searchReceiveItem" placeholder="search nama produk..."
                                class="block w-full rounded-xl border-0 bg-slate-50 py-2 px-3 text-xs text-slate-900 ring-1 ring-inset ring-slate-200 focus:ring-2 focus:ring-abs-green-600">
                        </div>

                        <!-- Form Submit Penerimaan (Hanya Input Qty Aktual) -->
                        <!-- Bagian Input Modal Konfirmasi Barang Datang -->
                        <form :action="'/purchase-orders/' + selectedPo.id + '/complete'" method="POST"
                            class="space-y-4">
                            @csrf
                            <div class="max-h-60 overflow-y-auto space-y-3 pr-1">
                                <template x-for="item in filteredReceiveItems" :key="item.id">
                                    <div class="p-3.5 rounded-2xl bg-slate-50 border border-slate-200/80 space-y-2">
                                        <div class="flex items-center justify-between">
                                            <span class="text-xs font-bold text-slate-900"
                                                x-text="item.product_name"></span>

                                            <!-- Informasi Qty Order & Harga Beli -->
                                            <div class="text-[11px] text-right">
                                                <span class="text-slate-500 font-medium">Qty Dipesan: <b
                                                        class="text-slate-800"
                                                        x-text="item.formatted_qty_ordered + ' ' + item.unit_name"></b></span>
                                                <span class="text-slate-400 font-normal ml-2"
                                                    x-text="'(' + formatRp(item.price) + ' / ' + item.unit_name + ')'"></span>
                                            </div>
                                        </div>

                                        <!-- KETERANGAN / LABEL DI SEBELAH INPUTAN QTY AKTUAL -->
                                        <div class="flex items-center gap-3">
                                            <label
                                                class="text-xs font-bold text-slate-700 whitespace-nowrap min-w-[130px]">
                                                Qty Datang:
                                            </label>

                                            <div class="relative flex-1 flex items-center">
                                                <input type="number" step="0.1"
                                                    :name="'items[' + item.id + '][qty_actual]'"
                                                    :value="item.actual_val"
                                                    class="block w-full rounded-xl border-0 bg-white py-2 pl-3 pr-16 text-xs text-slate-900 ring-1 ring-inset ring-slate-200 focus:ring-2 focus:ring-abs-green-600 font-extrabold"
                                                    placeholder="Masukan Qty Fisik Datang" required>
                                                <!-- Label Nama Satuan -->
                                                <span
                                                    class="absolute right-3 text-xs font-extrabold text-slate-500 pointer-events-none"
                                                    x-text="item.unit_name"></span>

                                                <!-- Hidden Input untuk Menjaga Nilai Harga Beli Asli -->
                                                <input type="hidden" :name="'items[' + item.id + '][price]'"
                                                    :value="item.price">
                                            </div>
                                        </div>
                                    </div>
                                </template>

                                <div x-show="filteredReceiveItems.length === 0"
                                    class="py-8 text-center text-xs text-slate-400">
                                    Produk tidak ditemukan.
                                </div>
                            </div>

                            <div class="pt-3 flex items-center justify-end gap-2 border-t border-slate-100">
                                <button type="button" @click="receiveModal = false"
                                    class="rounded-xl px-4 py-2.5 text-xs font-bold text-slate-600 hover:bg-slate-100">Batal</button>
                                <button type="submit"
                                    class="rounded-xl bg-abs-green-600 px-4 py-2.5 text-xs font-bold text-white shadow-lg hover:bg-abs-green-700 transition active:scale-95">
                                    Selesaikan & Tambah Stok Gudang
                                </button>
                            </div>
                        </form>

                    </div>
                </div>
            </div>
        </div>

    </div>
</x-dashboard-layout>
