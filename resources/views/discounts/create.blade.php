<x-dashboard-layout title="Buat Program Diskon Baru" active="discounts">
    <div x-data="{
        searchProduct: '',
        storeId: '{{ $defaultStoreId }}',
        stocks: {{ Js::from($storeStocks) }},
        selectedItems: [],
    
        init() {
            this.$watch('storeId', async (newVal) => {
                if (!newVal) return;
                try {
                    let res = await fetch('/discounts/store-products/' + newVal);
                    if (res.ok) {
                        this.stocks = await res.json();
                        this.selectedItems = [];
                    }
                } catch (e) { console.error('Gagal memuat produk toko.'); }
            });
        },
    
        get filteredStocks() {
            if (!this.searchProduct.trim()) return this.stocks;
            return this.stocks.filter(s => s.product && s.product.name.toLowerCase().includes(this.searchProduct.toLowerCase()));
        },
    
        toggleSelect(stock) {
            let idx = this.selectedItems.findIndex(i => i.product_id === stock.product_id);
            if (idx > -1) {
                this.selectedItems.splice(idx, 1);
            } else {
                let sellingPrice = stock.product ? parseFloat(stock.product.selling_price) || 0 : 0;
                this.selectedItems.push({
                    product_id: stock.product_id,
                    product_name: stock.product.name,
                    unit_name: stock.product.store_unit ? stock.product.store_unit.name : 'Kg',
                    original_price: sellingPrice,
                    discount_percentage: 10,
                    minimum_quantity: 1
                });
            }
        },
    
        isProductSelected(productId) {
            return this.selectedItems.some(i => i.product_id === productId);
        },
    
        calculateDiscountedPrice(item) {
            let original = parseFloat(item.original_price) || 0;
            let pct = parseFloat(item.discount_percentage) || 0;
            let discVal = original * (pct / 100);
            let finalPrice = original - discVal;
            return 'Rp ' + (finalPrice > 0 ? finalPrice : 0).toLocaleString('id-ID');
        }
    }" class="space-y-6">

        <!-- Header -->
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-2">
                <a href="{{ route('discounts.index') }}" class="text-slate-400 hover:text-slate-600 transition">
                    <i data-lucide="arrow-left" class="w-5 h-5"></i>
                </a>
                <h1 class="text-xl font-black text-slate-900">Buat Program Diskon Baru</h1>
            </div>
        </div>

        <form action="{{ route('discounts.store') }}" method="POST" class="space-y-6">
            @csrf

            <!-- Form Header Periode & Toko -->
            <div
                class="bg-white p-6 rounded-3xl border border-slate-200/80 shadow-sm grid grid-cols-1 sm:grid-cols-4 gap-4">
                <!-- Nama Program -->
                <div class="sm:col-span-1">
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">Nama Program
                        Diskon *</label>
                    <input type="text" name="name" placeholder="Misal: Promo Weekend Segar"
                        class="block w-full rounded-xl border-0 bg-slate-50 py-2.5 px-3 text-xs text-slate-900 ring-1 ring-inset ring-slate-200 focus:ring-2 focus:ring-abs-green-600 font-bold"
                        required>
                </div>

                <!-- Cabang Toko -->
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">Cabang Toko
                        *</label>
                    <select name="store_id" x-model="storeId"
                        class="block w-full rounded-xl border-0 bg-slate-50 py-2.5 px-3 text-xs text-slate-900 ring-1 ring-inset ring-slate-200 focus:ring-2 focus:ring-abs-green-600 font-bold"
                        required>
                        @foreach ($stores as $st)
                            <option value="{{ $st->id }}">{{ $st->name }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Waktu Mulai -->
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">Waktu Mulai
                        *</label>
                    <input type="datetime-local" name="start_at" value="{{ date('Y-m-d\TH:i') }}"
                        class="block w-full rounded-xl border-0 bg-slate-50 py-2.5 px-3 text-xs text-slate-900 ring-1 ring-inset ring-slate-200 focus:ring-2 focus:ring-abs-green-600"
                        required>
                </div>

                <!-- Waktu Selesai -->
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">Waktu Selesai
                        *</label>
                    <input type="datetime-local" name="end_at" value="{{ date('Y-m-d\TH:i', strtotime('+7 days')) }}"
                        class="block w-full rounded-xl border-0 bg-slate-50 py-2.5 px-3 text-xs text-slate-900 ring-1 ring-inset ring-slate-200 focus:ring-2 focus:ring-abs-green-600"
                        required>
                </div>
            </div>

            <!-- LAYOUT SPLIT 3/4 : 1/4 -->
            <div class="grid grid-cols-1 lg:grid-cols-4 gap-6 items-start">

                <!-- KIRI 3/4: KERANJANG DISKON PRODUK -->
                <div
                    class="lg:col-span-3 bg-white rounded-3xl border border-slate-200/80 shadow-sm overflow-hidden p-5 space-y-4">
                    <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                        <h3 class="text-sm font-bold text-slate-900">List Produk Diskon</h3>
                        <span class="text-xs font-bold text-slate-400"
                            x-text="selectedItems.length + ' Item Dipilih'"></span>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs">
                            <thead
                                class="bg-slate-50 text-slate-500 uppercase text-[10px] font-extrabold border-b border-slate-100">
                                <tr>
                                    <th class="py-2.5 px-4">Produk</th>
                                    <th class="py-2.5 px-4">Harga Beli Asli</th>
                                    <th class="py-2.5 px-4">Diskon (%)</th>
                                    <th class="py-2.5 px-4">Min Qty</th>
                                    <th class="py-2.5 px-4">Estimasi Harga Diskon</th>
                                    <th class="py-2.5 px-4 text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 text-slate-700">
                                <template x-for="(item, index) in selectedItems" :key="item.product_id">
                                    <tr class="hover:bg-slate-50/80 transition">
                                        <!-- Nama Produk & Hidden ID -->
                                        <td class="py-2.5 px-4 font-bold text-slate-900">
                                            <span x-text="item.product_name"></span>
                                            <input type="hidden" :name="'items[' + index + '][product_id]'"
                                                :value="item.product_id">
                                        </td>

                                        <!-- Harga Asli -->
                                        <td class="py-2.5 px-4 font-bold text-slate-600">
                                            Rp <span x-text="item.original_price.toLocaleString('id-ID')"></span>
                                            <span class="text-[10px] font-normal text-slate-400"
                                                x-text="'/ ' + item.unit_name"></span>
                                        </td>

                                        <!-- Input Persen Diskon -->
                                        <td class="py-2.5 px-4">
                                            <div class="relative flex items-center w-28">
                                                <input type="number" step="0.1" max="100" min="0.1"
                                                    :name="'items[' + index + '][discount_percentage]'"
                                                    x-model="item.discount_percentage"
                                                    class="block w-full rounded-xl border-0 bg-slate-50 py-2 pl-3 pr-8 text-xs font-bold text-slate-900 ring-1 ring-inset ring-slate-200 focus:ring-2 focus:ring-abs-green-600"
                                                    required>
                                                <span
                                                    class="absolute right-3 text-xs font-extrabold text-amber-600 pointer-events-none">%</span>
                                            </div>
                                        </td>

                                        <!-- Input Minimum Quantity + Nama Satuan Toko -->
                                        <td class="py-2.5 px-4">
                                            <div class="relative flex items-center w-32">
                                                <input type="number" step="0.1" min="1"
                                                    :name="'items[' + index + '][minimum_quantity]'"
                                                    x-model="item.minimum_quantity"
                                                    class="block w-full rounded-xl border-0 bg-slate-50 py-2 pl-3 pr-10 text-xs font-bold text-slate-900 ring-1 ring-inset ring-slate-200 focus:ring-2 focus:ring-abs-green-600"
                                                    required>
                                                <span
                                                    class="absolute right-2 text-[11px] font-extrabold text-slate-400 pointer-events-none"
                                                    x-text="item.unit_name"></span>
                                            </div>
                                        </td>

                                        <!-- Live Calculation Estimasi Harga Diskon -->
                                        <td class="py-2.5 px-4 font-black text-abs-green-700">
                                            <span x-text="calculateDiscountedPrice(item)"></span>
                                            <span class="text-[10px] font-bold text-slate-400"
                                                x-text="'/ ' + item.unit_name"></span>
                                        </td>

                                        <td class="py-2.5 px-4 text-center">
                                            <button type="button" @click="selectedItems.splice(index, 1)"
                                                class="p-1 rounded-lg text-red-500 hover:bg-red-50 hover:text-red-700 transition">
                                                <i data-lucide="trash-2" class="w-4 h-4"></i>
                                            </button>
                                        </td>
                                    </tr>
                                </template>
                                <tr x-show="selectedItems.length === 0">
                                    <td colspan="6" class="py-12 text-center text-slate-400">
                                        <i data-lucide="tag" class="w-8 h-8 mx-auto mb-2 text-slate-300"></i>
                                        <span>Belum ada produk dipilih. Klik produk dari tabel katalog kanan.</span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="pt-4 flex items-center justify-end gap-2 border-t border-slate-100">
                        <a href="{{ route('discounts.index') }}"
                            class="rounded-xl px-4 py-2.5 text-xs font-bold text-slate-600 hover:bg-slate-100 transition">Batal</a>
                        <button type="submit"
                            class="rounded-xl bg-abs-green-600 px-5 py-2.5 text-xs font-bold text-white shadow-lg hover:bg-abs-green-700 transition active:scale-95 disabled:opacity-50"
                            :disabled="selectedItems.length === 0">
                            Simpan Program Diskon
                        </button>
                    </div>
                </div>

                <!-- KANAN 1/4: KATALOG PRODUK STORE STOCK TOKO -->
                <div
                    class="lg:col-span-1 bg-white rounded-3xl border border-slate-200/80 shadow-sm overflow-hidden p-4 space-y-3">
                    <div>
                        <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wider">Katalog Produk Toko</h3>
                        <p class="text-[10px] text-slate-400">Diambil dari StoreStock toko ini</p>
                    </div>

                    <div>
                        <input type="text" x-model="searchProduct" placeholder="Search produk..."
                            class="block w-full rounded-xl border-0 bg-slate-50 py-1.5 px-3 text-xs text-slate-900 ring-1 ring-inset ring-slate-200 focus:ring-2 focus:ring-abs-green-600">
                    </div>

                    <div class="max-h-[420px] overflow-y-auto space-y-1 pr-1">
                        <template x-for="st in filteredStocks" :key="st.id">
                            <div @click="toggleSelect(st)"
                                class="p-2.5 rounded-2xl border transition cursor-pointer flex items-center justify-between"
                                :class="isProductSelected(st.product_id) ? 'bg-abs-green-50/60 border-abs-green-300' :
                                    'bg-slate-50 border-slate-100 hover:bg-slate-100'">
                                <div class="space-y-0.5 max-w-[140px]">
                                    <p class="text-xs font-bold text-slate-800 truncate"
                                        x-text="st.product ? st.product.name : '-'"></p>
                                    <span class="text-[10px] text-slate-500 font-bold block"
                                        x-text="'Harga: Rp ' + (st.product ? parseFloat(st.product.selling_price).toLocaleString('id-ID') : 0)"></span>
                                </div>

                                <button type="button" class="p-1 rounded-lg"
                                    :class="isProductSelected(st.product_id) ? 'bg-abs-green-600 text-white' :
                                        'bg-slate-200 text-slate-600'">
                                    <i data-lucide="check" x-show="isProductSelected(st.product_id)"
                                        class="w-3.5 h-3.5"></i>
                                    <i data-lucide="plus" x-show="!isProductSelected(st.product_id)"
                                        class="w-3.5 h-3.5"></i>
                                </button>
                            </div>
                        </template>

                        <div x-show="filteredStocks.length === 0" class="py-8 text-center text-xs text-slate-400">
                            Produk toko tidak ditemukan.
                        </div>
                    </div>
                </div>

            </div>
        </form>

    </div>
</x-dashboard-layout>
