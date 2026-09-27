<x-dashboard-layout title="Kelola Toko" active="stores">
    <div x-data="{
        addProductModal: false,
        opnameModal: false,
        detailModal: false,
        isSavingDraft: false,
    
        searchAddProduct: '',
        searchOpnameProduct: '',
        searchDetailItem: '',
    
        availableProducts: {{ Js::from($availableProducts) }},
        allStocks: {{ Js::from($allStoreStocks) }},
        activeOpname: {{ Js::from($activeOpname) }},
        selectedDetail: {},
    
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
    
        get filteredAvailableProducts() {
            if (!this.searchAddProduct.trim()) return this.availableProducts;
            return this.availableProducts.filter(p => p.name.toLowerCase().includes(this.searchAddProduct.toLowerCase()));
        },
    
        get filteredOpnameStocks() {
            let list = this.allStocks.map(st => {
                let draftItem = this.activeOpname && this.activeOpname.items ? this.activeOpname.items.find(i => i.product_id === st.product_id) : null;
                let rawVal = draftItem ? draftItem.actual_stock : st.stock;
                let actualVal = Math.round(rawVal * 10) / 10 == Math.floor(rawVal) ? Math.floor(rawVal) : Math.round(rawVal * 10) / 10;
    
                return {
                    product_id: st.product_id,
                    product_name: st.product ? st.product.name : '-',
                    unit_name: st.product && st.product.store_unit ? st.product.store_unit.name : '',
                    system_stock: st.stock,
                    formatted_system_stock: Math.round(st.stock * 10) / 10 == Math.floor(st.stock) ? Math.floor(st.stock) : Math.round(st.stock * 10) / 10,
                    actual_val: actualVal,
                    item_note: draftItem ? draftItem.note : ''
                };
            });
    
            if (!this.searchOpnameProduct.trim()) return list;
            return list.filter(item => item.product_name.toLowerCase().includes(this.searchOpnameProduct.toLowerCase()));
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
                let response = await fetch('{{ route('stores.opname.draft', $store->id) }}', {
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
                    Toast.fire({ icon: 'success', title: 'Draft opname toko berhasil disimpan!' });
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
                let response = await fetch('/stores/opname/' + adjustmentId + '/items', {
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

        <!-- Header Info Toko & List Kasir -->
        <div class="bg-white p-6 rounded-3xl border border-slate-200/80 shadow-sm space-y-4">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-slate-100 pb-4">
                <div>
                    <div class="flex items-center gap-2">
                       
                        <h1 class="text-xl font-black text-slate-900">{{ $store->name }}</h1>
                    </div>
                    <p class="text-xs text-slate-500 mt-1 flex items-center gap-1">
                        <i data-lucide="map-pin" class="w-3.5 h-3.5 text-slate-400"></i>
                        <span>{{ $store->address }}</span>
                    </p>
                </div>

                <div
                    class="bg-emerald-50 border border-emerald-200/60 rounded-2xl px-4 py-2.5 text-xs font-bold text-emerald-800 flex items-center gap-2">
                    <i data-lucide="users" class="w-4 h-4 text-emerald-600"></i>
                    <div>
                        <span class="text-[10px] uppercase tracking-wider block text-emerald-600 font-extrabold">Kasir
                            Penanggung Jawab:</span>
                        <span>{{ $cashierNames ?: 'Belum Ditugaskan' }}</span>
                    </div>
                </div>
            </div>

            <div class="flex flex-wrap items-center justify-between gap-3">
                <p class="text-xs font-bold text-slate-600">Total Produk di Toko: <span
                        class="text-abs-green-700 font-extrabold">{{ $allStoreStocks->count() }} SKU</span></p>

                <div class="flex items-center gap-2">
                    <button type="button" @click="addProductModal = true"
                        class="inline-flex items-center gap-2 rounded-xl bg-slate-800 px-4 py-2.5 text-xs font-bold text-white hover:bg-slate-900 transition active:scale-95">
                        <i data-lucide="plus-circle" class="w-4 h-4"></i>
                        <span>Tambahkan Produk</span>
                    </button>

                    <button type="button" @click="opnameModal = true"
                        class="inline-flex items-center gap-2 rounded-xl bg-abs-green-600 px-4 py-2.5 text-xs font-bold text-white shadow-lg shadow-abs-green-600/20 hover:bg-abs-green-700 transition active:scale-95">
                        <i data-lucide="clipboard-check" class="w-4 h-4"></i>
                        <span>{{ $activeOpname ? 'Lanjut Sesi Opname (Draft)' : 'Mulai Sesi Opname Toko' }}</span>
                    </button>
                </div>
            </div>
        </div>

        @include('stores.partials.stock-table')
        @include('stores.partials.history-table')
        @include('stores.partials.modals')

    </div>
</x-dashboard-layout>
