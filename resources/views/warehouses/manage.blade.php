<x-dashboard-layout title="Kelola Gudang" active="warehouses">
    <div x-data="{
        addProductModal: false,
        opnameModal: false,
        detailModal: false,
        isSavingDraft: false,
    
        // State Search Client-Side
        searchAddProduct: '',
        searchOpnameProduct: '',
        searchDetailItem: '',
    
        // Data Master
        availableProducts: {{ Js::from($availableProducts) }},
        allStocks: {{ Js::from($allWarehouseStocks) }},
        activeOpname: {{ Js::from($activeOpname) }},
        selectedDetail: {},
    
        // Filtered Getter Client-Side untuk Tambah Produk
        get filteredAvailableProducts() {
            if (!this.searchAddProduct.trim()) return this.availableProducts;
            return this.availableProducts.filter(p => p.name.toLowerCase().includes(this.searchAddProduct.toLowerCase()));
        },
    
        // Filtered Getter Client-Side untuk Form Opname
        get filteredOpnameStocks() {
            let list = this.allStocks.map(st => {
                let draftItem = this.activeOpname && this.activeOpname.items ? this.activeOpname.items.find(i => i.product_id === st.product_id) : null;
                let rawVal = draftItem ? draftItem.actual_stock : st.stock;
                let actualVal = Math.round(rawVal * 10) / 10 == Math.floor(rawVal) ? Math.floor(rawVal) : Math.round(rawVal * 10) / 10;
    
                return {
                    product_id: st.product_id,
                    product_name: st.product ? st.product.name : '-',
                    unit_name: st.product && st.product.warehouse_unit ? st.product.warehouse_unit.name : '',
                    system_stock: st.stock,
                    formatted_system_stock: Math.round(st.stock * 10) / 10 == Math.floor(st.stock) ? Math.floor(st.stock) : Math.round(st.stock * 10) / 10,
                    actual_val: actualVal,
                    item_note: draftItem ? draftItem.note : ''
                };
            });
    
            if (!this.searchOpnameProduct.trim()) return list;
            return list.filter(item => item.product_name.toLowerCase().includes(this.searchOpnameProduct.toLowerCase()));
        },
    
        // Helper format tanggal-waktu Indonesia
        formatDateTime(dateTimeStr) {
            if (!dateTimeStr) return '-';
            let date = new Date(dateTimeStr);
            return date.toLocaleDateString('id-ID', {
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
            if (!this.selectedDetail || !this.selectedDetail.items) return [];
    
            let isStore = this.selectedDetail.store_id !== null && this.selectedDetail.store_id !== undefined;
    
            let items = this.selectedDetail.items.map(item => {
                let adj = parseFloat(item.adjustment) || 0;
                let formattedAdj = this.formatNumber(Math.abs(adj));
    
                // Deteksi nama satuan berdasarkan tipe lokasi (Gudang = warehouseUnit, Toko = storeUnit)
                let unitName = '';
                if (item.product) {
                    if (isStore) {
                        unitName = item.product.store_unit ? item.product.store_unit.name : (item.product.warehouse_unit ? item.product.warehouse_unit.name : '');
                    } else {
                        unitName = item.product.warehouse_unit ? item.product.warehouse_unit.name : (item.product.store_unit ? item.product.store_unit.name : '');
                    }
                }
    
                return {
                    ...item,
                    unit_name: unitName,
                    formatted_system_stock: this.formatNumber(item.system_stock),
                    formatted_actual_stock: this.formatNumber(item.actual_stock),
                    formatted_adjustment: (adj > 0 ? '+' : (adj < 0 ? '-' : '')) + formattedAdj,
                    adj_value: adj
                };
            });
    
            if (!this.searchDetailItem.trim()) return items;
            return items.filter(i => i.product && i.product.name.toLowerCase().includes(this.searchDetailItem.toLowerCase()));
        },
    
        // Shortcut Ctrl + S untuk Simpan Draft
        init() {
            window.addEventListener('keydown', (e) => {
                if ((e.ctrlKey || e.metaKey) && e.key === 's') {
                    if (this.opnameModal) {
                        e.preventDefault();
                        this.saveDraft();
                    }
                }
            });
        },
    
        async saveDraft() {
            this.isSavingDraft = true;
            let form = document.getElementById('opnameForm');
            let formData = new FormData(form);
    
            try {
                let response = await fetch('{{ route('warehouses.opname.draft', $warehouse->id) }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: formData
                });
    
                let data = await response.json();
                if (response.ok && data.success) {
                    this.opnameModal = false;
                    Toast.fire({ icon: 'success', title: 'Draft opname berhasil disimpan!' });
                    setTimeout(() => { window.location.reload(); }, 500);
                } else {
                    Toast.fire({ icon: 'error', title: data.message || 'Gagal menyimpan draft.' });
                }
            } catch (err) {
                Toast.fire({ icon: 'error', title: 'Terjadi kesalahan sistem.' });
            } finally {
                this.isSavingDraft = false;
            }
        },
    
        async openDetailModal(adjustmentId) {
            try {
                let response = await fetch('/warehouses/opname/' + adjustmentId + '/items', {
                    headers: { 'Accept': 'application/json' }
                });
                if (response.ok) {
                    this.selectedDetail = await response.json();
                    this.searchDetailItem = '';
                    this.detailModal = true;
                }
            } catch (err) {
                Toast.fire({ icon: 'error', title: 'Gagal memuat rincian items.' });
            }
        }
    }" class="space-y-6">

        <!-- Header Info Gudang & List Supervisor -->
        <div class="bg-white p-6 rounded-3xl border border-slate-200/80 shadow-sm space-y-4">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-slate-100 pb-4">
                <div>
                    <div class="flex items-center gap-2">
                        <a href="{{ route('warehouses.index') }}" class="text-slate-400 hover:text-slate-600 transition">
                            <i data-lucide="arrow-left" class="w-5 h-5"></i>
                        </a>
                        <h1 class="text-xl font-black text-slate-900">{{ $warehouse->name }}</h1>
                    </div>
                    <p class="text-xs text-slate-500 mt-1 flex items-center gap-1">
                        <i data-lucide="map-pin" class="w-3.5 h-3.5 text-slate-400"></i>
                        <span>{{ $warehouse->address }}</span>
                    </p>
                </div>

                <div
                    class="bg-amber-50 border border-amber-200/60 rounded-2xl px-4 py-2.5 text-xs font-bold text-amber-800 flex items-center gap-2">
                    <i data-lucide="users" class="w-4 h-4 text-amber-600"></i>
                    <div>
                        <span
                            class="text-[10px] uppercase tracking-wider block text-amber-600 font-extrabold">Supervisor
                            Penanggung Jawab:</span>
                        <span>{{ $supervisorNames ?: 'Belum Ditugaskan' }}</span>
                    </div>
                </div>
            </div>

            <div class="flex flex-wrap items-center justify-between gap-3">
                <p class="text-xs font-bold text-slate-600">Total Produk di Gudang: <span
                        class="text-abs-green-700 font-extrabold">{{ $allWarehouseStocks->count() }} SKU</span></p>

                <div class="flex items-center gap-2">
                    <button type="button" @click="addProductModal = true"
                        class="inline-flex items-center gap-2 rounded-xl bg-slate-800 px-4 py-2.5 text-xs font-bold text-white hover:bg-slate-900 transition active:scale-95">
                        <i data-lucide="plus-circle" class="w-4 h-4"></i>
                        <span>Tambahkan Produk</span>
                    </button>

                    <button type="button" @click="opnameModal = true"
                        class="inline-flex items-center gap-2 rounded-xl bg-abs-green-600 px-4 py-2.5 text-xs font-bold text-white shadow-lg shadow-abs-green-600/20 hover:bg-abs-green-700 transition active:scale-95">
                        <i data-lucide="clipboard-check" class="w-4 h-4"></i>
                        <span>{{ $activeOpname ? 'Lanjut Sesi Opname (Draft)' : 'Mulai Sesi Opname' }}</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- Partial 1: Tabel Stok Barang Gudang (Server-Side) -->
        @include('warehouses.partials.stock-table')

        <!-- Partial 2: Tabel Riwayat Sesi Opname (Server-Side) -->
        @include('warehouses.partials.history-table')

        <!-- Partial 3: Seluruh Modal (Tambahkan Produk, Form Opname, Detail Riwayat) -->
        @include('warehouses.partials.modals')

    </div>
</x-dashboard-layout>
