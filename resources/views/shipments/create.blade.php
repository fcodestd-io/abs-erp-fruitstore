<x-dashboard-layout title="Buat Pengiriman Baru" active="shipments">
    <div x-data="{
        searchProduct: '',
        warehouseId: '{{ $defaultWarehouseId }}',
        stocks: {{ Js::from($warehouseStocks) }},
        selectedItems: [],
    
        init() {
            this.$watch('warehouseId', async (newVal) => {
                if (!newVal) return;
                try {
                    let res = await fetch('/shipments/warehouse-products/' + newVal);
                    if (res.ok) {
                        this.stocks = await res.json();
                        this.selectedItems = [];
                    }
                } catch (e) { console.error('Gagal memuat produk gudang.'); }
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
                this.selectedItems.push({
                    product_id: stock.product_id,
                    product_name: stock.product.name,
                    unit_name: stock.product.warehouse_unit ? stock.product.warehouse_unit.name : 'Dus',
                    max_stock: stock.stock,
                    qty_sent: 1
                });
            }
        },
    
        isProductSelected(productId) {
            return this.selectedItems.some(i => i.product_id === productId);
        }
    }" class="space-y-6">

        <!-- Header -->
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-2">
                <a href="{{ route('shipments.index') }}" class="text-slate-400 hover:text-slate-600 transition">
                    <i data-lucide="arrow-left" class="w-5 h-5"></i>
                </a>
                <h1 class="text-xl font-black text-slate-900">Buat Surat Jalan Pengiriman Baru</h1>
            </div>
        </div>

        <form action="{{ route('shipments.store') }}" method="POST" class="space-y-6">
            @csrf

            <!-- Form Header Shipment -->
            <div
                class="bg-white p-6 rounded-3xl border border-slate-200/80 shadow-sm grid grid-cols-1 sm:grid-cols-3 gap-4">
                <!-- Gudang Pengirim -->
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">Gudang Pengirim
                        *</label>
                    <select name="warehouse_id" x-model="warehouseId"
                        {{ auth()->user()->role === 'warehouse_supervisor' ? 'disabled' : '' }}
                        class="block w-full rounded-xl border-0 bg-slate-50 py-2.5 px-3 text-xs text-slate-900 ring-1 ring-inset ring-slate-200 focus:ring-2 focus:ring-abs-green-600 font-bold"
                        required>
                        @foreach ($warehouses as $wh)
                            <option value="{{ $wh->id }}">{{ $wh->name }}</option>
                        @endforeach
                    </select>
                    @if (auth()->user()->role === 'warehouse_supervisor')
                        <input type="hidden" name="warehouse_id" value="{{ auth()->user()->warehouse_id }}">
                    @endif
                </div>

                <!-- Toko Cabang Tujuan -->
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">Toko Cabang
                        Tujuan *</label>
                    <select name="store_id"
                        class="block w-full rounded-xl border-0 bg-slate-50 py-2.5 px-3 text-xs text-slate-900 ring-1 ring-inset ring-slate-200 focus:ring-2 focus:ring-abs-green-600 font-bold"
                        required>
                        <option value="">-- Pilih Toko Tujuan --</option>
                        @foreach ($stores as $st)
                            <option value="{{ $st->id }}">{{ $st->name }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Catatan Pengiriman -->
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">Catatan Surat
                        Jalan</label>
                    <input type="text" name="note" placeholder="Keterangan singkat pengiriman..."
                        class="block w-full rounded-xl border-0 bg-slate-50 py-2.5 px-3 text-xs text-slate-900 ring-1 ring-inset ring-slate-200 focus:ring-2 focus:ring-abs-green-600">
                </div>
            </div>

            <!-- LAYOUT SPLIT 3/4 : 1/4 -->
            <div class="grid grid-cols-1 lg:grid-cols-4 gap-6 items-start">

                <!-- KIRI 3/4: ITEM DIKIRIM -->
                <div
                    class="lg:col-span-3 bg-white rounded-3xl border border-slate-200/80 shadow-sm overflow-hidden p-5 space-y-4">
                    <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                        <h3 class="text-sm font-bold text-slate-900">List Barang Dikirim</h3>
                        <span class="text-xs font-bold text-slate-400"
                            x-text="selectedItems.length + ' Item Dipilih'"></span>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs">
                            <thead
                                class="bg-slate-50 text-slate-500 uppercase text-[10px] font-extrabold border-b border-slate-100">
                                <tr>
                                    <th class="py-2.5 px-4">Produk</th>
                                    <th class="py-2.5 px-4">Stok Maks Gudang</th>
                                    <th class="py-2.5 px-4">Qty Dikirim</th>
                                    <th class="py-2.5 px-4 text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 text-slate-700">
                                <template x-for="(item, index) in selectedItems" :key="item.product_id">
                                    <tr class="hover:bg-slate-50/80 transition">
                                        <td class="py-2.5 px-4 font-bold text-slate-900">
                                            <span x-text="item.product_name"></span>
                                            <input type="hidden" :name="'items[' + index + '][product_id]'"
                                                :value="item.product_id">
                                        </td>

                                        <td class="py-2.5 px-4 font-extrabold text-slate-600">
                                            <span x-text="item.max_stock"></span>
                                            <span class="text-[10px] font-bold text-slate-400 ml-0.5"
                                                x-text="item.unit_name"></span>
                                        </td>

                                        <!-- Input Qty Dikirim + Satuan Gudang -->
                                        <td class="py-2.5 px-4">
                                            <div class="relative flex items-center w-40">
                                                <input type="number" step="0.1" :max="item.max_stock"
                                                    :name="'items[' + index + '][qty_sent]'" x-model="item.qty_sent"
                                                    class="block w-full rounded-xl border-0 bg-slate-50 py-2 pl-3 pr-12 text-xs font-bold text-slate-900 ring-1 ring-inset ring-slate-200 focus:ring-2 focus:ring-abs-green-600"
                                                    required>
                                                <span
                                                    class="absolute right-3 text-xs font-extrabold text-slate-400 pointer-events-none"
                                                    x-text="item.unit_name"></span>
                                            </div>
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
                                    <td colspan="4" class="py-12 text-center text-slate-400">
                                        <i data-lucide="truck" class="w-8 h-8 mx-auto mb-2 text-slate-300"></i>
                                        <span>Belum ada produk dipilih. Klik produk di tabel kanan untuk
                                            menambahkan.</span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="pt-4 flex items-center justify-end gap-2 border-t border-slate-100">
                        <a href="{{ route('shipments.index') }}"
                            class="rounded-xl px-4 py-2.5 text-xs font-bold text-slate-600 hover:bg-slate-100 transition">Batal</a>
                        <button type="submit"
                            class="rounded-xl bg-abs-green-600 px-5 py-2.5 text-xs font-bold text-white shadow-lg hover:bg-abs-green-700 transition active:scale-95 disabled:opacity-50"
                            :disabled="selectedItems.length === 0">
                            Proses & Kirim Barang
                        </button>
                    </div>
                </div>

                <!-- KANAN 1/4: KATALOG PRODUK GUDANG PENGIRIM -->
                <div
                    class="lg:col-span-1 bg-white rounded-3xl border border-slate-200/80 shadow-sm overflow-hidden p-4 space-y-3">
                    <div>
                        <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wider">Katalog Produk Gudang</h3>
                        <p class="text-[10px] text-slate-400">Produk yang memiliki stok tersedia</p>
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
                                    <span class="text-[10px] text-slate-400 font-bold block"
                                        x-text="'Stok: ' + st.stock + ' ' + (st.product && st.product.warehouse_unit ? st.product.warehouse_unit.name : '')"></span>
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
                            Produk tidak ditemukan / stok kosong.
                        </div>
                    </div>
                </div>

            </div>
        </form>

    </div>
</x-dashboard-layout>
