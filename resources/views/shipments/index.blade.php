<x-dashboard-layout title="Pengiriman Barang (Shipment)" active="shipments">
    <div x-data="{
        detailModal: false,
        receiveModal: false,
        searchDetailItem: '',
        searchReceiveItem: '',
        selectedShipment: {},
    
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
    
            return Math.round(num * 10) / 10 == Math.floor(num) ?
                Math.floor(num).toLocaleString('id-ID') :
                (Math.round(num * 10) / 10).toLocaleString('id-ID');
        },
    
        get filteredDetailItems() {
            if (!this.selectedShipment || !this.selectedShipment.items) {
                return [];
            }
    
            let items = this.selectedShipment.items.map(item => ({
                ...item,
    
                product_name: item.product ?
                    item.product.name :
                    '-',
    
                wh_unit: item.product && item.product.warehouse_unit ?
                    item.product.warehouse_unit.name :
                    'Dus',
    
                st_unit: item.product && item.product.store_unit ?
                    item.product.store_unit.name :
                    'Kg',
    
                formatted_qty_sent: this.formatNumber(item.qty_sent),
    
                formatted_qty_received: this.formatNumber(item.qty_received),
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
    
        get filteredReceiveItems() {
            if (!this.selectedShipment || !this.selectedShipment.items) {
                return [];
            }
    
            let items = this.selectedShipment.items.map(item => {
    
                let rawQtyRec = item.qty_received > 0 ?
                    item.qty_received :
                    item.qty_sent;
    
                let numRec = parseFloat(rawQtyRec) || 0;
    
                let recVal = Math.round(numRec * 10) / 10 == Math.floor(numRec) ?
                    Math.floor(numRec) :
                    Math.round(numRec * 10) / 10;
    
                let numSent = parseFloat(item.qty_sent) || 0;
    
                let sentVal = Math.round(numSent * 10) / 10 == Math.floor(numSent) ?
                    Math.floor(numSent) :
                    Math.round(numSent * 10) / 10;
    
                return {
                    ...item,
    
                    product_name: item.product ?
                        item.product.name :
                        '-',
    
                    wh_unit: item.product && item.product.warehouse_unit ?
                        item.product.warehouse_unit.name :
                        'Dus',
    
                    st_unit: item.product && item.product.store_unit ?
                        item.product.store_unit.name :
                        'Kg',
    
                    formatted_qty_sent: sentVal,
    
                    received_val: recVal,
                };
            });
    
            if (!this.searchReceiveItem.trim()) {
                return items;
            }
    
            return items.filter(i =>
                i.product_name
                .toLowerCase()
                .includes(this.searchReceiveItem.toLowerCase())
            );
        },
    
        async openDetailModal(id) {
            try {
                let res = await fetch('/shipments/' + id + '/items');
    
                if (res.ok) {
                    this.selectedShipment = await res.json();
                    this.searchDetailItem = '';
                    this.detailModal = true;
                }
            } catch (err) {
                Toast.fire({
                    icon: 'error',
                    title: 'Gagal memuat rincian pengiriman.'
                });
            }
        },
    
        async openReceiveModal(id) {
            try {
                let res = await fetch('/shipments/' + id + '/items');
    
                if (res.ok) {
                    this.selectedShipment = await res.json();
                    this.searchReceiveItem = '';
                    this.receiveModal = true;
                }
            } catch (err) {
                Toast.fire({
                    icon: 'error',
                    title: 'Gagal memuat data pengiriman.'
                });
            }
        }
    }" class="space-y-6">

        <!-- Controls Header -->
        <div
            class="bg-white p-5 rounded-3xl border border-slate-200/80 shadow-sm flex flex-col sm:flex-row items-center justify-between gap-4">

            <div>
                <h1 class="text-base font-black text-slate-900">
                    Pengiriman Barang Gudang ke Toko (Shipment)
                </h1>

                <p class="text-xs text-slate-400">
                    Distribusi stok produk dari gudang utama ke toko cabang eceran
                </p>
            </div>

            @if (in_array(auth()->user()->role, ['owner', 'admin', 'warehouse_supervisor']))
                <a href="{{ route('shipments.create') }}"
                    class="w-full sm:w-auto inline-flex items-center justify-center gap-2 rounded-xl bg-abs-green-600 px-4 py-2.5 text-xs font-bold text-white shadow-lg shadow-abs-green-600/20 hover:bg-abs-green-700 transition active:scale-95">
                    <i data-lucide="plus" class="w-4 h-4"></i>
                    <span>Buat Pengiriman Baru</span>
                </a>
            @endif

        </div>

        <!-- Filter Server Side -->
        <div class="bg-white p-5 rounded-3xl border border-slate-200/80 shadow-sm space-y-3">

            <form action="{{ route('shipments.index') }}" method="GET" class="grid grid-cols-1 sm:grid-cols-5 gap-2">

                <!-- Search -->
                <div>
                    <input type="text" name="search" value="{{ $search }}"
                        placeholder="Kode Surat Jalan / Note..."
                        class="block w-full rounded-xl border-0 bg-slate-50 py-2 px-3 text-xs text-slate-900 ring-1 ring-inset ring-slate-200 focus:ring-2 focus:ring-abs-green-600">
                </div>

                <!-- Status -->
                <div>
                    <select name="status"
                        class="block w-full rounded-xl border-0 bg-slate-50 py-2 px-3 text-xs text-slate-900 ring-1 ring-inset ring-slate-200 focus:ring-2 focus:ring-abs-green-600">
                        <option value="">
                            -- Semua Status --
                        </option>

                        <option value="pending" {{ $status === 'pending' ? 'selected' : '' }}>
                            PENDING (DALAM PERJALANAN)
                        </option>

                        <option value="completed" {{ $status === 'completed' ? 'selected' : '' }}>
                            COMPLETED (DITERIMA TOKO)
                        </option>

                        <option value="canceled" {{ $status === 'canceled' ? 'selected' : '' }}>
                            CANCELED (BATAL)
                        </option>
                    </select>
                </div>

                <!-- Warehouse -->
                @if (auth()->user()->role !== 'warehouse_supervisor')
                    <div>
                        <select name="warehouse_id"
                            class="block w-full rounded-xl border-0 bg-slate-50 py-2 px-3 text-xs text-slate-900 ring-1 ring-inset ring-slate-200 focus:ring-2 focus:ring-abs-green-600">
                            <option value="">
                                -- Semua Gudang Asal --
                            </option>

                            @foreach ($warehouses as $wh)
                                <option value="{{ $wh->id }}" {{ $warehouseId == $wh->id ? 'selected' : '' }}>
                                    {{ $wh->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                @endif

                <!-- Store -->
                @if (auth()->user()->role !== 'cashier')
                    <div>
                        <select name="store_id"
                            class="block w-full rounded-xl border-0 bg-slate-50 py-2 px-3 text-xs text-slate-900 ring-1 ring-inset ring-slate-200 focus:ring-2 focus:ring-abs-green-600">
                            <option value="">
                                -- Semua Toko Tujuan --
                            </option>

                            @foreach ($stores as $st)
                                <option value="{{ $st->id }}" {{ $storeId == $st->id ? 'selected' : '' }}>
                                    {{ $st->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                @endif

                <!-- Month -->
                <div class="flex gap-2">

                    <select name="month"
                        class="block w-full rounded-xl border-0 bg-slate-50 py-2 px-3 text-xs text-slate-900 ring-1 ring-inset ring-slate-200 focus:ring-2 focus:ring-abs-green-600">
                        <option value="">
                            -- Semua Bulan --
                        </option>

                        @for ($m = 1; $m <= 12; $m++)
                            <option value="{{ $m }}" {{ $month == $m ? 'selected' : '' }}>
                                {{ DateTime::createFromFormat('!m', $m)->format('F') }}
                            </option>
                        @endfor
                    </select>

                    <button type="submit"
                        class="px-3 py-2 bg-slate-800 text-white rounded-xl text-xs font-bold hover:bg-slate-900 transition">
                        Filter
                    </button>

                </div>

            </form>
        </div>

        <!-- Tabel Riwayat Shipment -->
        <div class="bg-white rounded-3xl border border-slate-200/80 shadow-sm overflow-hidden">

            <div class="overflow-x-auto">

                <table class="w-full text-left text-xs">

                    <thead
                        class="bg-slate-50 text-slate-500 uppercase text-[10px] font-extrabold tracking-wider border-b border-slate-100">
                        <tr>
                            <th class="py-3.5 px-5">
                                Kode & Gudang Asal
                            </th>

                            <th class="py-3.5 px-5">
                                Toko Tujuan & Penerima
                            </th>

                            <th class="py-3.5 px-5">
                                Pengirim (SPV)
                            </th>

                            <th class="py-3.5 px-5">
                                Status
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

                        @forelse ($shipments as $shp)

                            <tr class="hover:bg-slate-50/80 transition">

                                <!-- Kode & Gudang Asal -->
                                <td class="py-3.5 px-5">

                                    <span class="font-black text-slate-900 block text-xs">
                                        {{ $shp->code }}
                                    </span>

                                    <span
                                        class="text-[11px] font-medium text-slate-500 block mt-0.5 flex items-center gap-1">
                                        <i data-lucide="warehouse" class="w-3 h-3 text-slate-400 inline"></i>

                                        <span>
                                            {{ $shp->warehouse->name ?? '-' }}
                                        </span>
                                    </span>

                                    @if ($shp->note)
                                        <p class="text-[10px] text-slate-400 italic mt-0.5 truncate max-w-xs">
                                            {{ $shp->note }}
                                        </p>
                                    @endif

                                </td>

                                <!-- Toko Tujuan & Penerima -->
                                <td class="py-3.5 px-5">

                                    <span class="font-bold text-slate-800 block flex items-center gap-1">
                                        <i data-lucide="store" class="w-3 h-3 text-emerald-600 inline"></i>

                                        <span>
                                            {{ $shp->store->name ?? '-' }}
                                        </span>
                                    </span>

                                    <span class="text-[11px] font-medium text-slate-400 block mt-0.5">
                                        Kasir:
                                        <b class="text-slate-600">
                                            {{ $shp->cashier->username ?? '-' }}
                                        </b>
                                    </span>

                                </td>

                                <!-- Pengirim (SPV) -->
                                <td class="py-3.5 px-5 font-bold text-slate-700">
                                    {{ $shp->warehouseSupervisor->username ?? '-' }}
                                </td>

                                <!-- Status -->
                                <td class="py-3.5 px-5">

                                    @if ($shp->status === 'pending')
                                        <span
                                            class="inline-flex items-center px-2.5 py-1 rounded-lg bg-amber-100 text-amber-800 font-extrabold text-[10px] uppercase">
                                            ● PENDING (DIKIRIM)
                                        </span>
                                    @elseif ($shp->status === 'completed')
                                        <span
                                            class="inline-flex items-center px-2.5 py-1 rounded-lg bg-emerald-100 text-emerald-800 font-extrabold text-[10px] uppercase">
                                            ✓ COMPLETED
                                        </span>
                                    @else
                                        <span
                                            class="inline-flex items-center px-2.5 py-1 rounded-lg bg-red-100 text-red-800 font-extrabold text-[10px] uppercase">
                                            ✕ CANCELED
                                        </span>
                                    @endif

                                </td>

                                <!-- Waktu Transaksi -->
                                <td class="py-3.5 px-5">

                                    <span class="text-slate-700 font-medium block">
                                        {{ $shp->created_at->format('d M Y, H:i') }}
                                    </span>

                                    @if ($shp->completed_at)
                                        <span class="text-[10px] font-bold text-emerald-600 block mt-0.5">
                                            Diterima:
                                            {{ $shp->completed_at->format('d M Y, H:i') }}
                                        </span>
                                    @elseif ($shp->canceled_at)
                                        <span class="text-[10px] font-bold text-red-500 block mt-0.5">
                                            Batal:
                                            {{ $shp->canceled_at->format('d M Y, H:i') }}
                                        </span>
                                    @endif

                                </td>

                                <!-- Aksi -->
                                <td class="py-3.5 px-5 text-center">

                                    <div class="flex items-center justify-center gap-1.5">

                                        <!-- Detail -->
                                        <button type="button" @click="openDetailModal({{ $shp->id }})"
                                            class="p-2 rounded-xl bg-slate-100 text-slate-700 hover:bg-slate-200 transition"
                                            title="Lihat Detail Items">
                                            <i data-lucide="eye" class="w-3.5 h-3.5"></i>
                                        </button>

                                        @if ($shp->status === 'pending')
                                            <!-- Terima Barang -->
                                            @if (in_array(auth()->user()->role, ['owner', 'admin', 'cashier']))
                                                <button type="button" @click="openReceiveModal({{ $shp->id }})"
                                                    class="px-3 py-1.5 rounded-xl bg-amber-500 text-white hover:bg-amber-600 font-bold text-[11px] shadow-sm transition active:scale-95">
                                                    Terima Barang
                                                </button>
                                            @endif

                                            <!-- Batalkan Pengiriman -->
                                            @if (in_array(auth()->user()->role, ['owner', 'admin', 'warehouse_supervisor']))
                                                <button type="button"
                                                    @click="$dispatch('open-confirm', {
                                                        title: 'Batalkan Pengiriman',
                                                        message: 'Apakah Anda yakin ingin membatalkan pengiriman {{ $shp->code }}? Stok akan dikembalikan ke gudang.',
                                                        confirmText: 'Ya, Batalkan',
                                                        variant: 'danger',
                                                        actionUrl: '{{ route('shipments.cancel', $shp->id) }}',
                                                        actionMethod: 'POST'
                                                    })"
                                                    class="px-2.5 py-1.5 rounded-xl bg-red-50 text-red-600 hover:bg-red-100 font-bold text-[11px] transition">
                                                    Batalkan
                                                </button>
                                            @endif
                                        @endif

                                    </div>

                                </td>

                            </tr>

                        @empty

                            <tr>
                                <td colspan="6" class="py-12 text-center text-slate-400">
                                    Data pengiriman barang tidak ditemukan.
                                </td>
                            </tr>

                        @endforelse

                    </tbody>

                </table>

            </div>

            @if ($shipments->hasPages())
                <div class="p-4 border-t border-slate-100">
                    {{ $shipments->links() }}
                </div>
            @endif

        </div>

        <!-- MODAL 1: DETAIL ITEMS SHIPMENT -->
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
                                    x-text="'Detail Pengiriman (' + (selectedShipment.code || '') + ')'"></h3>

                                <p class="text-xs text-slate-500"
                                    x-text="'Dari: ' + (selectedShipment.warehouse ? selectedShipment.warehouse.name : '-') + ' → Ke: ' + (selectedShipment.store ? selectedShipment.store.name : '-')">
                                </p>

                            </div>

                            <button @click="detailModal = false" type="button"
                                class="text-slate-400 hover:text-slate-600">
                                <i data-lucide="x" class="w-5 h-5"></i>
                            </button>

                        </div>

                        <div>
                            <input type="text" x-model="searchDetailItem"
                                placeholder="Live search barang di rincian ini..."
                                class="block w-full rounded-xl border-0 bg-slate-50 py-2 px-3 text-xs text-slate-900 ring-1 ring-inset ring-slate-200 focus:ring-2 focus:ring-abs-green-600">
                        </div>

                        <div class="max-h-60 overflow-y-auto border border-slate-100 rounded-2xl">

                            <table class="w-full text-left text-xs">

                                <thead
                                    class="bg-slate-50 text-slate-500 uppercase text-[10px] font-extrabold border-b border-slate-100">
                                    <tr>
                                        <th class="py-2.5 px-3">
                                            Produk
                                        </th>

                                        <th class="py-2.5 px-3">
                                            Qty Dikirim (Gudang)
                                        </th>

                                        <th class="py-2.5 px-3">
                                            Qty Diterima (Toko)
                                        </th>
                                    </tr>
                                </thead>

                                <tbody class="divide-y divide-slate-100 text-slate-700">

                                    <template x-for="item in filteredDetailItems" :key="item.id">

                                        <tr class="hover:bg-slate-50">

                                            <td class="py-2.5 px-3 font-bold text-slate-900"
                                                x-text="item.product_name"></td>

                                            <td class="py-2.5 px-3">

                                                <span x-text="item.formatted_qty_sent"></span>

                                                <span class="text-[10px] font-bold text-slate-400 ml-0.5"
                                                    x-text="item.wh_unit"></span>

                                            </td>

                                            <td class="py-2.5 px-3 font-bold text-emerald-700">

                                                <span x-text="item.formatted_qty_received"></span>

                                                <span class="text-[10px] font-bold text-slate-400 ml-0.5"
                                                    x-text="item.st_unit"></span>

                                            </td>

                                        </tr>

                                    </template>

                                </tbody>

                            </table>

                        </div>

                        <div class="pt-2 flex items-center justify-end">

                            <button type="button" @click="detailModal = false"
                                class="rounded-xl px-4 py-2.5 text-xs font-bold text-slate-600 hover:bg-slate-100">
                                Tutup
                            </button>

                        </div>

                    </div>

                </div>

            </div>

        </div>

        <!-- MODAL 2: KONFIRMASI TERIMA BARANG DI TOKO -->
        <div x-show="receiveModal" x-cloak class="relative z-50">

            <div x-show="receiveModal" class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm"
                @click="receiveModal = false"></div>

            <div class="fixed inset-0 z-10 overflow-y-auto">

                <div class="flex min-h-full items-center justify-center p-4">

                    <div
                        class="relative w-full max-w-2xl transform overflow-hidden rounded-3xl bg-white p-6 shadow-2xl transition-all border border-slate-100 space-y-4">

                        <div class="flex items-start justify-between border-b border-slate-100 pb-3">

                            <div>

                                <h3 class="text-base font-bold text-slate-900"
                                    x-text="'Konfirmasi Penerimaan Toko (' + (selectedShipment.code || '') + ')'"></h3>

                                <p class="text-xs text-slate-400">
                                    Input kuantitas fisik eceran yang diterima toko
                                </p>

                            </div>

                            <button @click="receiveModal = false" type="button"
                                class="text-slate-400 hover:text-slate-600">
                                <i data-lucide="x" class="w-5 h-5"></i>
                            </button>

                        </div>

                        <div
                            class="grid grid-cols-1 sm:grid-cols-3 gap-2 bg-slate-50 p-3 rounded-2xl border border-slate-100 text-[11px]">

                            <div>
                                <span class="text-slate-400 block font-bold">
                                    GUDANG PENGIRIM
                                </span>

                                <span class="font-bold text-slate-800"
                                    x-text="selectedShipment.warehouse ? selectedShipment.warehouse.name : '-'"></span>
                            </div>

                            <div>
                                <span class="text-slate-400 block font-bold">
                                    TOKO PENERIMA
                                </span>

                                <span class="font-bold text-slate-800"
                                    x-text="selectedShipment.store ? selectedShipment.store.name : '-'"></span>
                            </div>

                            <div>
                                <span class="text-slate-400 block font-bold">
                                    WAKTU KIRIM
                                </span>

                                <span class="font-bold text-slate-800"
                                    x-text="formatDateTime(selectedShipment.created_at)"></span>
                            </div>

                        </div>

                        <div>

                            <input type="text" x-model="searchReceiveItem"
                                placeholder="Live search nama produk..."
                                class="block w-full rounded-xl border-0 bg-slate-50 py-2 px-3 text-xs text-slate-900 ring-1 ring-inset ring-slate-200 focus:ring-2 focus:ring-abs-green-600">

                        </div>

                        <form :action="'/shipments/' + selectedShipment.id + '/complete'" method="POST"
                            class="space-y-4">

                            @csrf

                            <div class="max-h-60 overflow-y-auto space-y-3 pr-1">

                                <template x-for="item in filteredReceiveItems" :key="item.id">

                                    <div class="p-3.5 rounded-2xl bg-slate-50 border border-slate-200/80 space-y-2">

                                        <div class="flex items-center justify-between">

                                            <span class="text-xs font-bold text-slate-900"
                                                x-text="item.product_name"></span>

                                            <div class="text-[11px] text-right">

                                                <span class="text-slate-500 font-medium">

                                                    Qty Dikirim:

                                                    <b class="text-slate-800"
                                                        x-text="item.formatted_qty_sent + ' ' + item.wh_unit"></b>

                                                </span>

                                            </div>

                                        </div>

                                        <div class="flex items-center gap-3">

                                            <label
                                                class="text-xs font-bold text-slate-700 whitespace-nowrap min-w-[130px]">
                                                Qty Diterima (Eceran):
                                            </label>

                                            <div class="relative flex-1 flex items-center">

                                                <input type="number" step="0.1"
                                                    :name="'items[' + item.id + '][qty_received]'"
                                                    :value="item.received_val"
                                                    class="block w-full rounded-xl border-0 bg-white py-2.5 pl-3 pr-16 text-xs text-slate-900 ring-1 ring-inset ring-slate-200 focus:ring-2 focus:ring-abs-green-600 font-extrabold"
                                                    placeholder="Input Qty Diterima Toko" required>

                                                <span
                                                    class="absolute right-3 text-xs font-extrabold text-emerald-600 pointer-events-none"
                                                    x-text="item.st_unit"></span>

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
                                    class="rounded-xl px-4 py-2.5 text-xs font-bold text-slate-600 hover:bg-slate-100">
                                    Batal
                                </button>

                                <button type="submit"
                                    class="rounded-xl bg-abs-green-600 px-4 py-2.5 text-xs font-bold text-white shadow-lg hover:bg-abs-green-700 transition active:scale-95">
                                    Selesaikan & Tambah Stok Toko
                                </button>

                            </div>

                        </form>

                    </div>

                </div>

            </div>

        </div>

    </div>
</x-dashboard-layout>
