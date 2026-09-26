<!-- MODAL 1: TAMBAHKAN PRODUK KE TOKO -->
<div x-show="addProductModal" x-cloak class="relative z-50">
    <div x-show="addProductModal" class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm" @click="addProductModal = false">
    </div>
    <div class="fixed inset-0 z-10 overflow-y-auto">
        <div class="flex min-h-full items-center justify-center p-4">
            <div
                class="relative w-full max-w-lg transform overflow-hidden rounded-3xl bg-white p-6 shadow-2xl transition-all border border-slate-100">
                <div class="flex items-center justify-between pb-4 border-b border-slate-100">
                    <h3 class="text-base font-bold text-slate-900">Pilih Produk ke Toko</h3>
                    <button @click="addProductModal = false" type="button"
                        class="text-slate-400 hover:text-slate-600"><i data-lucide="x" class="w-5 h-5"></i></button>
                </div>

                <div class="mt-3">
                    <input type="text" x-model="searchAddProduct" placeholder="Search nama produk..."
                        class="block w-full rounded-xl border-0 bg-slate-50 py-2 px-3 text-xs text-slate-900 ring-1 ring-inset ring-slate-200 focus:ring-2 focus:ring-abs-green-600">
                </div>

                <form action="{{ route('stores.add-products', $store->id) }}" method="POST" class="mt-4 space-y-4">
                    @csrf
                    <div class="max-h-72 overflow-y-auto space-y-2 pr-1">
                        <template x-for="prod in filteredAvailableProducts" :key="prod.id">
                            <label
                                class="flex items-center justify-between p-3 rounded-2xl bg-slate-50 border border-slate-100 hover:bg-slate-100 cursor-pointer transition">
                                <div class="flex items-center gap-3">
                                    <input type="checkbox" name="product_ids[]" :value="prod.id"
                                        class="w-4 h-4 rounded text-abs-green-600 focus:ring-abs-green-600 border-slate-300">
                                    <span class="text-xs font-bold text-slate-800" x-text="prod.name"></span>
                                </div>
                                <span class="text-[10px] font-bold text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded"
                                    x-text="'Satuan Toko: ' + (prod.store_unit ? prod.store_unit.name : '-')"></span>
                            </label>
                        </template>
                        <div x-show="filteredAvailableProducts.length === 0"
                            class="py-8 text-center text-xs text-slate-400">
                            Produk tidak ditemukan / semua sudah ada di toko ini.
                        </div>
                    </div>

                    <div class="pt-4 flex items-center justify-end gap-2 border-t border-slate-100">
                        <button type="button" @click="addProductModal = false"
                            class="rounded-xl px-4 py-2.5 text-xs font-bold text-slate-600 hover:bg-slate-100">Batal</button>
                        <button type="submit"
                            class="rounded-xl bg-abs-green-600 px-4 py-2.5 text-xs font-bold text-white hover:bg-abs-green-700">Tambahkan
                            Produk Dipilih</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- MODAL 2: SESI STOK OPNAME TOKO (Input + Satuan Toko) -->
<div x-show="opnameModal" x-cloak class="relative z-50">
    <div x-show="opnameModal" class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm" @click="opnameModal = false"></div>
    <div class="fixed inset-0 z-10 overflow-y-auto">
        <div class="flex min-h-full items-center justify-center p-4">
            <div
                class="relative w-full max-w-2xl transform overflow-hidden rounded-3xl bg-white p-6 shadow-2xl transition-all border border-slate-100">
                <div class="flex items-center justify-between pb-4 border-b border-slate-100">
                    <div>
                        <h3 class="text-base font-bold text-slate-900">
                            {{ $activeOpname ? 'Lanjutkan Opname (' . $activeOpname->code . ')' : 'Sesi Stok Opname Toko Baru' }}
                        </h3>
                        <p class="text-xs text-slate-400">Tekan <kbd
                                class="px-1.5 py-0.5 bg-slate-100 border rounded text-[10px] font-bold">Ctrl + S</kbd>
                            untuk simpan draft & tutup modal.</p>
                    </div>
                    <button @click="opnameModal = false" type="button" class="text-slate-400 hover:text-slate-600"><i
                            data-lucide="x" class="w-5 h-5"></i></button>
                </div>

                <form id="opnameForm"
                    action="{{ route('stores.opname.complete', [$store->id, $activeOpname->id ?? '']) }}" method="POST"
                    class="mt-4 space-y-4">
                    @csrf
                    <input type="hidden" name="adjustment_id" id="adjustment_id_input"
                        value="{{ $activeOpname->id ?? '' }}">

                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">Catatan
                            Header (Sesi)</label>
                        <textarea name="note" rows="2"
                            class="block w-full rounded-xl border-0 bg-slate-50 py-2.5 px-3 text-xs text-slate-900 ring-1 ring-inset ring-slate-200 focus:ring-2 focus:ring-abs-green-600"
                            placeholder="Keterangan singkat sesi opname toko...">{{ $activeOpname->note ?? '' }}</textarea>
                    </div>

                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-700">Input Hasil
                                Fisik Barang Toko (Max 1 Desimal)</label>
                            <span class="text-[10px] text-slate-400 font-bold"
                                x-text="filteredOpnameStocks.length + ' Item Ditampilkan'"></span>
                        </div>
                        <input type="text" x-model="searchOpnameProduct" placeholder="Search nama produk..."
                            class="block w-full rounded-xl border-0 bg-slate-50 py-2 px-3 text-xs text-slate-900 ring-1 ring-inset ring-slate-200 focus:ring-2 focus:ring-abs-green-600 mb-2">
                    </div>

                    <div class="max-h-64 overflow-y-auto space-y-3 pr-1">
                        <template x-for="st in filteredOpnameStocks" :key="st.product_id">
                            <div class="p-3.5 rounded-2xl bg-slate-50 border border-slate-200/80 space-y-2">
                                <div class="flex items-center justify-between">
                                    <span class="text-xs font-bold text-slate-900" x-text="st.product_name"></span>
                                    <span class="text-[11px] font-bold text-slate-500">
                                        Stok Sistem: <b class="text-slate-800"
                                            x-text="st.formatted_system_stock + ' ' + st.unit_name"></b>
                                    </span>
                                </div>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                    <!-- Input Stok Fisik Beserta Label Satuan di Sampingnya -->
                                    <div class="relative flex items-center">
                                        <input type="number" step="0.1"
                                            :name="'actual_stocks[' + st.product_id + ']'" :value="st.actual_val"
                                            class="block w-full rounded-xl border-0 bg-white py-2 pl-3 pr-12 text-xs text-slate-900 ring-1 ring-inset ring-slate-200 focus:ring-2 focus:ring-abs-green-600 font-bold"
                                            placeholder="Stok Fisik">
                                        <span
                                            class="absolute right-3 text-xs font-extrabold text-slate-400 pointer-events-none"
                                            x-text="st.unit_name"></span>
                                    </div>

                                    <input type="text" :name="'item_notes[' + st.product_id + ']'"
                                        :value="st.item_note"
                                        class="block w-full rounded-xl border-0 bg-white py-2 px-3 text-xs text-slate-900 ring-1 ring-inset ring-slate-200 focus:ring-2 focus:ring-abs-green-600"
                                        placeholder="Catatan item (misal: 1 kg busuk)">
                                </div>
                            </div>
                        </template>
                    </div>

                    <div class="pt-4 flex items-center justify-between border-t border-slate-100">
                        <button type="button" @click="opnameModal = false"
                            class="rounded-xl px-4 py-2.5 text-xs font-bold text-slate-600 hover:bg-slate-100">Batal</button>
                        <div class="flex items-center gap-2">
                            <button type="button" @click="saveDraft()"
                                class="rounded-xl bg-amber-500 hover:bg-amber-600 px-4 py-2.5 text-xs font-bold text-white shadow-md"
                                :disabled="isSavingDraft">
                                <span x-show="!isSavingDraft">Simpan Draft (Ctrl+S)</span>
                                <span x-show="isSavingDraft">Menyimpan...</span>
                            </button>
                            <button type="submit"
                                class="rounded-xl bg-abs-green-600 px-4 py-2.5 text-xs font-bold text-white shadow-lg hover:bg-abs-green-700">Tandai
                                Selesai & Apply Stok</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- MODAL 3: DETAIL ITEMS RIWAYAT OPNAME TOKO (Stok + Satuan Lengkap) -->
<div x-show="detailModal" x-cloak class="relative z-50">
    <div x-show="detailModal" class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm" @click="detailModal = false">
    </div>
    <div class="fixed inset-0 z-10 overflow-y-auto">
        <div class="flex min-h-full items-center justify-center p-4">
            <div
                class="relative w-full max-w-2xl transform overflow-hidden rounded-3xl bg-white p-6 shadow-2xl transition-all border border-slate-100 space-y-4">

                <div class="flex items-start justify-between border-b border-slate-100 pb-4">
                    <div class="space-y-1">
                        <div class="flex items-center gap-2">
                            <h3 class="text-base font-bold text-slate-900"
                                x-text="'Detail Opname (' + (selectedDetail.code || '') + ')'"></h3>
                            <template x-if="!selectedDetail.completed_at && !selectedDetail.canceled_at">
                                <span
                                    class="px-2.5 py-0.5 rounded-lg bg-amber-100 text-amber-800 font-extrabold text-[10px] uppercase">●
                                    STARTED / DRAFT</span>
                            </template>
                            <template x-if="selectedDetail.completed_at">
                                <span
                                    class="px-2.5 py-0.5 rounded-lg bg-emerald-100 text-emerald-800 font-extrabold text-[10px] uppercase">✓
                                    COMPLETED</span>
                            </template>
                            <template x-if="selectedDetail.canceled_at">
                                <span
                                    class="px-2.5 py-0.5 rounded-lg bg-red-100 text-red-800 font-extrabold text-[10px] uppercase">✕
                                    CANCELED</span>
                            </template>
                        </div>
                        <p class="text-xs text-slate-500">
                            Operator: <b class="text-slate-800"
                                x-text="selectedDetail.operator ? selectedDetail.operator.username : '-'"></b>
                        </p>
                    </div>

                    <button @click="detailModal = false" type="button"
                        class="text-slate-400 hover:text-slate-600"><i data-lucide="x" class="w-5 h-5"></i></button>
                </div>

                <div
                    class="grid grid-cols-1 sm:grid-cols-3 gap-2 bg-slate-50 p-3 rounded-2xl border border-slate-100 text-[11px]">
                    <div>
                        <span class="text-slate-400 block font-bold">WAKTU DIMULAI</span>
                        <span class="font-bold text-slate-700"
                            x-text="formatDateTime(selectedDetail.created_at)"></span>
                    </div>
                    <div>
                        <span class="text-slate-400 block font-bold">WAKTU SELESAI</span>
                        <span class="font-bold"
                            :class="selectedDetail.completed_at ? 'text-emerald-700' : 'text-slate-400'"
                            x-text="formatDateTime(selectedDetail.completed_at)"></span>
                    </div>
                    <div>
                        <span class="text-slate-400 block font-bold">WAKTU DIBATALKAN</span>
                        <span class="font-bold" :class="selectedDetail.canceled_at ? 'text-red-600' : 'text-slate-400'"
                            x-text="formatDateTime(selectedDetail.canceled_at)"></span>
                    </div>
                </div>

                <div>
                    <input type="text" x-model="searchDetailItem"
                        placeholder="Search produk..."
                        class="block w-full rounded-xl border-0 bg-slate-50 py-2 px-3 text-xs text-slate-900 ring-1 ring-inset ring-slate-200 focus:ring-2 focus:ring-abs-green-600">
                </div>

                <div class="max-h-60 overflow-y-auto border border-slate-100 rounded-2xl">
                    <table class="w-full text-left text-xs">
                        <thead
                            class="bg-slate-50 text-slate-500 uppercase text-[10px] font-extrabold border-b border-slate-100">
                            <tr>
                                <th class="py-2.5 px-3">Produk</th>
                                <th class="py-2.5 px-3">Stok Sistem</th>
                                <th class="py-2.5 px-3">Stok Fisik</th>
                                <th class="py-2.5 px-3">Selisih</th>
                                <th class="py-2.5 px-3">Catatan</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-slate-700">
                            <template x-for="item in filteredDetailItems" :key="item.id">
                                <tr class="hover:bg-slate-50">
                                    <td class="py-2.5 px-3 font-bold text-slate-900"
                                        x-text="item.product ? item.product.name : '-'"></td>

                                    <!-- Stok Sistem + Nama Satuan Toko -->
                                    <td class="py-2.5 px-3">
                                        <span x-text="item.formatted_system_stock"></span>
                                        <span class="text-[10px] font-bold text-slate-400 ml-0.5"
                                            x-text="item.unit_name"></span>
                                    </td>

                                    <!-- Stok Fisik + Nama Satuan Toko -->
                                    <td class="py-2.5 px-3 font-bold text-slate-900">
                                        <span x-text="item.formatted_actual_stock"></span>
                                        <span class="text-[10px] font-bold text-slate-400 ml-0.5"
                                            x-text="item.unit_name"></span>
                                    </td>

                                    <!-- Selisih + Nama Satuan Toko -->
                                    <td class="py-2.5 px-3 font-bold"
                                        :class="item.adj_value < 0 ? 'text-red-600' : (item.adj_value > 0 ? 'text-emerald-600' :
                                            'text-slate-500')">
                                        <span x-text="item.formatted_adjustment"></span>
                                        <span class="text-[10px] font-bold ml-0.5" x-text="item.unit_name"></span>
                                    </td>

                                    <td class="py-2.5 px-3 text-slate-500" x-text="item.note || '-'"></td>
                                </tr>
                            </template>
                            <tr x-show="filteredDetailItems.length === 0">
                                <td colspan="5" class="py-8 text-center text-slate-400">Item tidak ditemukan.</td>
                            </tr>
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
