<x-dashboard-layout title="POS Kasir Penjualan" active="pos">
    <div x-data="{
        searchProduct: '',
        storeId: '{{ $defaultStoreId }}',
        stocks: {{ Js::from($storeStocks) }},
        discounts: {{ Js::from($activeDiscounts) }},
        cart: [],
        paymentMethod: 'cash',
        paidAmount: '',
        note: '',
    
        init() {
            // Otomatis buka popup cetak struk jika ada perintah dari transaksi sebelumnya
            @if (session('print_sale_id')) window.open('/sales/{{ session('print_sale_id') }}/receipt', '_blank', 'width=400,height=600'); @endif
    
            // Watcher perubahan toko cabang
            this.$watch('storeId', async (newVal) => {
                if (!newVal) return;
                try {
                    let res = await fetch('/sales/store-products/' + newVal);
                    if (res.ok) {
                        let data = await res.json();
                        this.stocks = data.stocks;
                        this.discounts = data.discounts;
                        this.cart = [];
                    }
                } catch (e) { console.error('Gagal memuat produk toko.'); }
            });
    
            // Watcher metode pembayaran: Jika non-cash (QRIS/Transfer), otomatis set paidAmount = grandTotal
            this.$watch('paymentMethod', (val) => {
                if (val !== 'cash') {
                    this.paidAmount = this.grandTotal;
                } else {
                    this.paidAmount = '';
                }
            });
        },
    
        get filteredStocks() {
            if (!this.searchProduct.trim()) return this.stocks;
            return this.stocks.filter(s => s.product && s.product.name.toLowerCase().includes(this.searchProduct.toLowerCase()));
        },
    
        addToCart(stock) {
            let existing = this.cart.find(i => i.product_id === stock.product_id);
            if (existing) {
                if (existing.qty < stock.stock) {
                    existing.qty = Math.round((existing.qty + 1) * 10) / 10;
                    this.recalculateItemDiscount(existing);
                } else {
                    Toast.fire({ icon: 'warning', title: 'Stok toko tidak mencukupi!' });
                }
            } else {
                let sellingPrice = stock.product ? parseFloat(stock.product.selling_price) || 0 : 0;
                let item = {
                    product_id: stock.product_id,
                    product_name: stock.product.name,
                    unit_name: stock.product.store_unit ? stock.product.store_unit.name : 'Kg',
                    max_stock: stock.stock,
                    price: sellingPrice,
                    qty: 1,
                    discount: 0
                };
                this.recalculateItemDiscount(item);
                this.cart.push(item);
            }
        },
    
        recalculateItemDiscount(item) {
            let promo = this.discounts.find(d => d.product_id === item.product_id && item.qty >= parseFloat(d.minimum_quantity));
            if (promo) {
                let pct = parseFloat(promo.discount_percentage) || 0;
                let rawDisc = (item.qty * item.price) * (pct / 100);
                item.discount = Math.round(rawDisc);
            } else {
                item.discount = 0;
            }
        },
    
        updateQty(item, newQty) {
            let qty = parseFloat(newQty) || 0;
            if (qty > item.max_stock) {
                qty = item.max_stock;
                Toast.fire({ icon: 'warning', title: 'Mencapai batas maks stok toko.' });
            }
            item.qty = qty;
            this.recalculateItemDiscount(item);
        },
    
        get subtotal() {
            return this.cart.reduce((sum, i) => sum + (i.qty * i.price), 0);
        },
    
        get totalDiscount() {
            return this.cart.reduce((sum, i) => sum + i.discount, 0);
        },
    
        get grandTotal() {
            return Math.max(0, this.subtotal - this.totalDiscount);
        },
    
        get changeAmount() {
            if (this.paymentMethod !== 'cash') return 0;
            let paid = parseFloat(this.paidAmount) || 0;
            return Math.max(0, paid - this.grandTotal);
        },
    
        get isPaymentValid() {
            if (this.cart.length === 0) return false;
            if (this.paymentMethod === 'cash') {
                return (parseFloat(this.paidAmount) || 0) >= this.grandTotal;
            }
            return true;
        },
    
        formatRp(val) {
            return 'Rp ' + (parseFloat(val) || 0).toLocaleString('id-ID');
        },
    
        formatNumber(val) {
            let num = parseFloat(val) || 0;
            let rounded = Math.round(num * 10) / 10;
            return rounded == Math.floor(rounded) ? Math.floor(rounded) : rounded.toString().replace('.', ',');
        }
    }" class="space-y-4">

        <!-- POS Header Controls -->
        <div
            class="bg-white p-4 rounded-3xl border border-slate-200/80 shadow-sm flex flex-col sm:flex-row items-center justify-between gap-4">
            <div class="flex items-center gap-3">
                <div class="p-2.5 bg-abs-green-50 text-abs-green-700 rounded-2xl">
                    <i data-lucide="shopping-bag" class="w-6 h-6"></i>
                </div>
                <div>
                    <h1 class="text-base font-black text-slate-900">Kasir Penjualan (POS)</h1>
                    <p class="text-xs text-slate-400">Transaksi eceran cepat toko Alam Buah Segar</p>
                </div>
            </div>

            <!-- Pilih Toko Cabang -->
            <div class="w-full sm:w-64">
                <select name="store_id" x-model="storeId" {{ auth()->user()->role === 'cashier' ? 'disabled' : '' }}
                    class="block w-full rounded-2xl border-0 bg-slate-50 py-2 px-3 text-xs font-bold text-slate-900 ring-1 ring-inset ring-slate-200 focus:ring-2 focus:ring-abs-green-600">
                    @foreach ($stores as $st)
                        <option value="{{ $st->id }}">{{ $st->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <!-- Form Eksekusi Penjualan -->
        <form action="{{ route('sales.store') }}" method="POST">
            @csrf
            <input type="hidden" name="store_id" :value="storeId">

            <!-- MAIN SPLIT-SCREEN GRID (3/4 Keranjang POS : 1/4 Katalog Toko) -->
            <div class="grid grid-cols-1 lg:grid-cols-4 gap-6 items-start">

                <!-- KIRI 3/4: KERANJANG BELANJA & KASIR CHECKOUT -->
                <div class="lg:col-span-3 space-y-4">

                    <div
                        class="bg-white rounded-3xl border border-slate-200/80 shadow-sm overflow-hidden p-5 space-y-4">
                        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                            <h3 class="text-sm font-black text-slate-900">Item Belanjaan</h3>
                            <span class="text-xs font-bold text-slate-400"
                                x-text="cart.length + ' Jenis Produk'"></span>
                        </div>

                        <!-- Tabel Cart Items -->
                        <div class="overflow-x-auto max-h-[320px]">
                            <table class="w-full text-left text-xs">
                                <thead
                                    class="bg-slate-50 text-slate-500 uppercase text-[10px] font-extrabold border-b border-slate-100">
                                    <tr>
                                        <th class="py-2.5 px-3">Produk</th>
                                        <th class="py-2.5 px-3">Harga</th>
                                        <th class="py-2.5 px-3">Qty Belanja</th>
                                        <th class="py-2.5 px-3">Diskon Promo</th>
                                        <th class="py-2.5 px-3">Subtotal</th>
                                        <th class="py-2.5 px-3 text-center">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 text-slate-700">
                                    <template x-for="(item, index) in cart" :key="item.product_id">
                                        <tr class="hover:bg-slate-50/80 transition">
                                            <td class="py-2.5 px-3 font-bold text-slate-900">
                                                <span x-text="item.product_name"></span>
                                                <input type="hidden" :name="'items[' + index + '][product_id]'"
                                                    :value="item.product_id">
                                            </td>

                                            <td class="py-2.5 px-3 font-medium text-slate-600">
                                                <span x-text="formatRp(item.price)"></span>
                                                <input type="hidden" :name="'items[' + index + '][price]'"
                                                    :value="item.price">
                                            </td>

                                            <!-- Input Qty Eceran -->
                                            <td class="py-2.5 px-3">
                                                <div class="relative flex items-center w-32">
                                                    <input type="number" step="0.1" min="0.1"
                                                        :max="item.max_stock" :name="'items[' + index + '][qty]'"
                                                        :value="item.qty"
                                                        @input="updateQty(item, $event.target.value)"
                                                        class="block w-full rounded-xl border-0 bg-slate-50 py-1.5 pl-3 pr-10 text-xs font-extrabold text-slate-900 ring-1 ring-inset ring-slate-200 focus:ring-2 focus:ring-abs-green-600"
                                                        required>
                                                    <span
                                                        class="absolute right-2 text-[10px] font-extrabold text-slate-400 pointer-events-none"
                                                        x-text="item.unit_name"></span>
                                                </div>
                                            </td>

                                            <!-- Diskon Item -->
                                            <td class="py-2.5 px-3 font-bold text-amber-600">
                                                <span
                                                    x-text="item.discount > 0 ? '-' + formatRp(item.discount) : '-'"></span>
                                                <input type="hidden" :name="'items[' + index + '][discount]'"
                                                    :value="item.discount">
                                            </td>

                                            <!-- Subtotal -->
                                            <td class="py-2.5 px-3 font-black text-abs-green-700">
                                                <span x-text="formatRp((item.qty * item.price) - item.discount)"></span>
                                            </td>

                                            <td class="py-2.5 px-3 text-center">
                                                <button type="button" @click="cart.splice(index, 1)"
                                                    class="p-1 text-red-500 hover:bg-red-50 rounded-lg transition">
                                                    <i data-lucide="trash-2" class="w-4 h-4"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    </template>
                                    <tr x-show="cart.length === 0">
                                        <td colspan="6" class="py-12 text-center text-slate-400">
                                            <i data-lucide="shopping-cart"
                                                class="w-8 h-8 mx-auto mb-2 text-slate-300"></i>
                                            <span>Keranjang kosong. Klik produk di katalog sebelah kanan.</span>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- RINGKASAN & PEMBAYARAN KASIR -->
                    <div class="bg-slate-900 text-white rounded-3xl p-6 shadow-xl space-y-4">
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 border-b border-slate-800 pb-4">
                            <div>
                                <span
                                    class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Subtotal</span>
                                <p class="text-base font-bold text-slate-200" x-text="formatRp(subtotal)"></p>
                            </div>
                            <div>
                                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Total
                                    Diskon</span>
                                <p class="text-base font-bold text-amber-400" x-text="'-' + formatRp(totalDiscount)">
                                </p>
                            </div>
                            <div>
                                <span class="text-[10px] font-bold text-amber-400 uppercase tracking-wider">TOTAL
                                    TAGIHAN</span>
                                <p class="text-2xl font-black text-emerald-400" x-text="formatRp(grandTotal)"></p>
                            </div>
                        </div>

                        <!-- Form Metode Bayar & Nominal -->
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 items-end">
                            <!-- Select Metode Pembayaran -->
                            <div>
                                <label class="block text-xs font-bold text-slate-300 mb-1">Metode Bayar</label>
                                <select name="payment_method" x-model="paymentMethod"
                                    class="block w-full rounded-xl border-0 bg-slate-800 py-2.5 px-3 text-xs text-white ring-1 ring-inset ring-slate-700 focus:ring-2 focus:ring-emerald-500 font-bold">
                                    <option value="cash">TUNAI / CASH</option>
                                    <option value="qris">QRIS</option>
                                    <option value="transfer">TRANSFER BANK</option>
                                </select>
                            </div>

                            <!-- Input Uang Diterima (Dinamis: Input Manual saat Cash, Otomatis Pas saat QRIS/Transfer) -->
                            <div>
                                <label class="block text-xs font-bold text-slate-300 mb-1">Uang Diterima (Rp)</label>

                                <template x-if="paymentMethod === 'cash'">
                                    <input type="number" name="paid_amount" x-model="paidAmount"
                                        class="block w-full rounded-xl border-0 bg-slate-800 py-2.5 px-3 text-xs text-white ring-1 ring-inset ring-slate-700 focus:ring-2 focus:ring-emerald-500 font-black"
                                        placeholder="0" required>
                                </template>

                                <template x-if="paymentMethod !== 'cash'">
                                    <div>
                                        <div
                                            class="py-2.5 px-3 bg-slate-800/80 rounded-xl text-slate-300 font-black text-xs border border-slate-700/50 flex items-center justify-between">
                                            <span x-text="formatRp(grandTotal)"></span>
                                            <span class="text-[10px] text-emerald-400 font-normal">(Pas / Lunas)</span>
                                        </div>
                                        <input type="hidden" name="paid_amount" :value="grandTotal">
                                    </div>
                                </template>
                            </div>

                            <!-- Kembalian -->
                            <div>
                                <span class="block text-xs font-bold text-slate-300 mb-1">Kembalian</span>
                                <div
                                    class="py-2.5 px-3 bg-slate-800 rounded-xl text-emerald-400 font-black text-sm border border-slate-700/50">
                                    <span x-text="formatRp(changeAmount)"></span>
                                </div>
                            </div>
                        </div>

                        <!-- Catatan & Tombol Eksekusi Bayar -->
                        <div class="pt-2 flex items-center justify-between gap-4">
                            <input type="text" name="note" x-model="note"
                                placeholder="Catatan transaksi (opsional)..."
                                class="block w-2/3 rounded-xl border-0 bg-slate-800 py-2.5 px-3 text-xs text-white ring-1 ring-inset ring-slate-700 focus:ring-2 focus:ring-emerald-500">

                            <button type="submit" :disabled="!isPaymentValid"
                                class="w-1/3 py-3 rounded-xl bg-emerald-500 text-slate-950 font-black text-xs hover:bg-emerald-400 transition shadow-lg disabled:opacity-40 disabled:cursor-not-allowed">
                                BAYAR & CETAK STRUK
                            </button>
                        </div>
                    </div>

                </div>

                <!-- KANAN 1/4: KATALOG PRODUK ECRAN TOKO -->
                <div
                    class="lg:col-span-1 bg-white rounded-3xl border border-slate-200/80 shadow-sm overflow-hidden p-4 space-y-3">
                    <div>
                        <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wider">Katalog Produk Toko</h3>
                        <p class="text-[10px] text-slate-400">Klik produk untuk tambah ke keranjang</p>
                    </div>

                    <div>
                        <input type="text" x-model="searchProduct" placeholder="Search produk..."
                            class="block w-full rounded-xl border-0 bg-slate-50 py-1.5 px-3 text-xs text-slate-900 ring-1 ring-inset ring-slate-200 focus:ring-2 focus:ring-abs-green-600">
                    </div>

                    <div class="max-h-[520px] overflow-y-auto space-y-1.5 pr-1">
                        <template x-for="st in filteredStocks" :key="st.id">
                            <div @click="addToCart(st)"
                                class="p-2.5 rounded-2xl bg-slate-50 border border-slate-100 hover:bg-slate-100 hover:border-abs-green-300 transition cursor-pointer flex items-center justify-between">
                                <div class="space-y-0.5 max-w-[130px]">
                                    <p class="text-xs font-bold text-slate-800 truncate"
                                        x-text="st.product ? st.product.name : '-'"></p>
                                    <span class="text-[10px] text-emerald-700 font-extrabold block"
                                        x-text="formatRp(st.product ? st.product.selling_price : 0) + ' / ' + (st.product && st.product.store_unit ? st.product.store_unit.name : '')"></span>
                                </div>

                                <div class="text-right">
                                    <span class="text-[10px] font-bold text-slate-400 block"
                                        x-text="'Stok: ' + formatNumber(st.stock)"></span>
                                    <span class="inline-block p-1 bg-abs-green-600 text-white rounded-lg mt-0.5">
                                        <i data-lucide="plus" class="w-3 h-3"></i>
                                    </span>
                                </div>
                            </div>
                        </template>

                        <div x-show="filteredStocks.length === 0" class="py-8 text-center text-xs text-slate-400">
                            Produk tidak ditemukan / stok habis.
                        </div>
                    </div>
                </div>

            </div>
        </form>

    </div>
</x-dashboard-layout>
