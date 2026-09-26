<x-dashboard-layout title="Manajemen Diskon" active="discounts">
    <div x-data="{
        detailModal: false,
        searchDetailItem: '',
        selectedDiscount: {},
    
        formatDateTime(dtStr) {
            if (!dtStr) return '-';
            let d = new Date(dtStr);
            return d.toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' });
        },
    
        // Helper format angka desimal max 1 angka dengan koma (locale Indonesia)
        formatNumber(val) {
            let num = parseFloat(val) || 0;
            let rounded = Math.round(num * 10) / 10;
            return rounded == Math.floor(rounded) ?
                Math.floor(rounded).toString() :
                rounded.toString().replace('.', ',');
        },
    
        formatRp(val) {
            return 'Rp ' + (parseFloat(val) || 0).toLocaleString('id-ID');
        },
    
        get filteredDetailItems() {
            if (!this.selectedDiscount || !this.selectedDiscount.items) return [];
    
            let items = this.selectedDiscount.items.map(item => {
                let originalPrice = item.product ? parseFloat(item.product.selling_price) || 0 : 0;
                let discPctRaw = parseFloat(item.discount_percentage) || 0;
                let discPrice = originalPrice - (originalPrice * discPctRaw / 100);
    
                return {
                    ...item,
                    product_name: item.product ? item.product.name : '-',
                    unit_name: item.product && item.product.store_unit ? item.product.store_unit.name : 'Kg',
                    formatted_disc_pct: this.formatNumber(item.discount_percentage),
                    formatted_min_qty: this.formatNumber(item.minimum_quantity),
                    original_price_fmt: this.formatRp(originalPrice),
                    disc_price_fmt: this.formatRp(discPrice),
                };
            });
    
            if (!this.searchDetailItem.trim()) return items;
            return items.filter(i => i.product_name.toLowerCase().includes(this.searchDetailItem.toLowerCase()));
        },
    
        async openDetailModal(id) {
            try {
                let res = await fetch('/discounts/' + id + '/items');
                if (res.ok) {
                    this.selectedDiscount = await res.json();
                    this.searchDetailItem = '';
                    this.detailModal = true;
                }
            } catch (err) { Toast.fire({ icon: 'error', title: 'Gagal memuat rincian diskon.' }); }
        }
    }" class="space-y-6">

        <!-- Header Controls -->
        <div
            class="bg-white p-5 rounded-3xl border border-slate-200/80 shadow-sm flex flex-col sm:flex-row items-center justify-between gap-4">
            <div>
                <h1 class="text-base font-black text-slate-900">Program Diskon & Promosi Toko</h1>
                <p class="text-xs text-slate-400">Pengaturan periode diskon produk eceran khusus Owner</p>
            </div>

            <a href="{{ route('discounts.create') }}"
                class="w-full sm:w-auto inline-flex items-center justify-center gap-2 rounded-xl bg-abs-green-600 px-4 py-2.5 text-xs font-bold text-white shadow-lg shadow-abs-green-600/20 hover:bg-abs-green-700 transition active:scale-95">
                <i data-lucide="plus" class="w-4 h-4"></i>
                <span>Buat Program Diskon</span>
            </a>
        </div>

        <!-- Filter Server Side -->
        <div class="bg-white p-5 rounded-3xl border border-slate-200/80 shadow-sm space-y-3">
            <form action="{{ route('discounts.index') }}" method="GET" class="grid grid-cols-1 sm:grid-cols-4 gap-2">
                <div>
                    <input type="text" name="search" value="{{ $search }}"
                        placeholder="Nama Program / Toko..."
                        class="block w-full rounded-xl border-0 bg-slate-50 py-2 px-3 text-xs text-slate-900 ring-1 ring-inset ring-slate-200 focus:ring-2 focus:ring-abs-green-600">
                </div>

                <div>
                    <select name="status"
                        class="block w-full rounded-xl border-0 bg-slate-50 py-2 px-3 text-xs text-slate-900 ring-1 ring-inset ring-slate-200 focus:ring-2 focus:ring-abs-green-600">
                        <option value="">-- Semua Status --</option>
                        <option value="active" {{ $status === 'active' ? 'selected' : '' }}>BERLANGSUNG (ACTIVE)
                        </option>
                        <option value="upcoming" {{ $status === 'upcoming' ? 'selected' : '' }}>AKAN DATANG (UPCOMING)
                        </option>
                        <option value="expired" {{ $status === 'expired' ? 'selected' : '' }}>SELESAI / EXPIRED</option>
                    </select>
                </div>

                <div>
                    <select name="store_id"
                        class="block w-full rounded-xl border-0 bg-slate-50 py-2 px-3 text-xs text-slate-900 ring-1 ring-inset ring-slate-200 focus:ring-2 focus:ring-abs-green-600">
                        <option value="">-- Semua Toko Cabang --</option>
                        @foreach ($stores as $st)
                            <option value="{{ $st->id }}" {{ $storeId == $st->id ? 'selected' : '' }}>
                                {{ $st->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="flex gap-2">
                    <button type="submit"
                        class="w-full px-3 py-2 bg-slate-800 text-white rounded-xl text-xs font-bold hover:bg-slate-900 transition">Filter</button>
                </div>
            </form>
        </div>

        <!-- Tabel Program Diskon -->
        <div class="bg-white rounded-3xl border border-slate-200/80 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead
                        class="bg-slate-50 text-slate-500 uppercase text-[10px] font-extrabold tracking-wider border-b border-slate-100">
                        <tr>
                            <th class="py-3.5 px-5">Nama Program & Toko</th>
                            <th class="py-3.5 px-5">Periode Diskon</th>
                            <th class="py-3.5 px-5">Total Produk</th>
                            <th class="py-3.5 px-5">Status</th>
                            <th class="py-3.5 px-5 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-slate-700">
                        @php $now = now(); @endphp
                        @forelse ($discounts as $disc)
                            @php
                                $isActive = $disc->start_at <= $now && $disc->end_at >= $now;
                                $isUpcoming = $disc->start_at > $now;
                                $isExpired = $disc->end_at < $now;
                            @endphp
                            <tr class="hover:bg-slate-50/80 transition">
                                <!-- Nama Program & Toko -->
                                <td class="py-3.5 px-5">
                                    <span class="font-black text-slate-900 block text-xs">{{ $disc->name }}</span>
                                    <span
                                        class="text-[11px] font-medium text-slate-500 block mt-0.5 flex items-center gap-1">
                                        <i data-lucide="store" class="w-3 h-3 text-emerald-600 inline"></i>
                                        <span>{{ $disc->store->name ?? '-' }}</span>
                                    </span>
                                </td>

                                <!-- Periode Diskon -->
                                <td class="py-3.5 px-5">
                                    <span  
                                        class="text-slate-700 font-bold block">{{ $disc->start_at->format('d M Y, H:i') }}</span>
                                    <span class="text-[10px] font-bold text-slate-400 block mt-0.5">
                                        s/d {{ $disc->end_at->format('d M Y, H:i') }}
                                    </span>
                                </td>

                                <!-- Total Item -->
                                <td class="py-3.5 px-5 font-extrabold text-slate-800">
                                    {{ $disc->items->count() }} Produk
                                </td>

                                <!-- Status Badge -->
                                <td class="py-3.5 px-5">
                                    @if ($isActive)
                                        <span
                                            class="inline-flex items-center px-2.5 py-1 rounded-lg bg-emerald-100 text-emerald-800 font-extrabold text-[10px] uppercase">
                                            ● BERLANGSUNG
                                        </span>
                                    @elseif($isUpcoming)
                                        <span
                                            class="inline-flex items-center px-2.5 py-1 rounded-lg bg-amber-100 text-amber-800 font-extrabold text-[10px] uppercase">
                                            ⏳ AKAN DATANG
                                        </span>
                                    @else
                                        <span
                                            class="inline-flex items-center px-2.5 py-1 rounded-lg bg-slate-100 text-slate-500 font-extrabold text-[10px] uppercase">
                                            ✓ SELESAI / EXPIRED
                                        </span>
                                    @endif
                                </td>

                                <!-- Aksi -->
                                <td class="py-3.5 px-5 text-center">
                                    <div class="flex items-center justify-center gap-1.5">
                                        <button type="button" @click="openDetailModal({{ $disc->id }})"
                                            class="p-2 rounded-xl bg-slate-100 text-slate-700 hover:bg-slate-200 transition"
                                            title="Lihat Detail Produk Diskon">
                                            <i data-lucide="eye" class="w-3.5 h-3.5"></i>
                                        </button>

                                        @if (!$isExpired)
                                            <button type="button"
                                                @click="$dispatch('open-confirm', {
                                                    title: 'Akhiri Diskon',
                                                    message: 'Apakah Anda yakin ingin mengakhiri program diskon \'{{ addslashes($disc->name) }}\' sekarang?',
                                                    confirmText: 'Ya, Akhiri Sekarang',
                                                    variant: 'danger',
                                                    actionUrl: '{{ route('discounts.end-now', $disc->id) }}',
                                                    actionMethod: 'POST'
                                                })"
                                                class="px-2.5 py-1.5 rounded-xl bg-red-50 text-red-600 hover:bg-red-100 font-bold text-[11px] transition">
                                                Akhiri Diskon
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="py-12 text-center text-slate-400">Data program diskon tidak
                                    ditemukan.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($discounts->hasPages())
                <div class="p-4 border-t border-slate-100">{{ $discounts->links() }}</div>
            @endif
        </div>

        <!-- MODAL DETAIL PRODUK DISKON -->
        <div x-show="detailModal" x-cloak class="relative z-50">
            <div x-show="detailModal" class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm"
                @click="detailModal = false"></div>
            <div class="fixed inset-0 z-10 overflow-y-auto">
                <div class="flex min-h-full items-center justify-center p-4">
                    <div
                        class="relative w-full max-w-2xl transform overflow-hidden rounded-3xl bg-white p-6 shadow-2xl transition-all border border-slate-100 space-y-4">
                        <div class="flex items-start justify-between border-b border-slate-100 pb-3">
                            <div>
                                <h3 class="text-base font-bold text-slate-900"
                                    x-text="'Detail Diskon (' + (selectedDiscount.name || '') + ')'"></h3>
                                <p class="text-xs text-slate-500"
                                    x-text="'Toko: ' + (selectedDiscount.store ? selectedDiscount.store.name : '-')">
                                </p>
                            </div>
                            <button @click="detailModal = false" type="button"
                                class="text-slate-400 hover:text-slate-600"><i data-lucide="x"
                                    class="w-5 h-5"></i></button>
                        </div>

                        <div>
                            <input type="text" x-model="searchDetailItem" placeholder="Live search produk diskon..."
                                class="block w-full rounded-xl border-0 bg-slate-50 py-2 px-3 text-xs text-slate-900 ring-1 ring-inset ring-slate-200 focus:ring-2 focus:ring-abs-green-600">
                        </div>

                        <div class="max-h-60 overflow-y-auto border border-slate-100 rounded-2xl">
                            <table class="w-full text-left text-xs">
                                <thead
                                    class="bg-slate-50 text-slate-500 uppercase text-[10px] font-extrabold border-b border-slate-100">
                                    <tr>
                                        <th class="py-2.5 px-3">Produk</th>
                                        <th class="py-2.5 px-3">Harga Asli</th>
                                        <th class="py-2.5 px-3">Diskon (%)</th>
                                        <th class="py-2.5 px-3">Min Qty</th>
                                        <th class="py-2.5 px-3">Estimasi Harga Diskon</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 text-slate-700">
                                    <template x-for="item in filteredDetailItems" :key="item.id">
                                        <tr class="hover:bg-slate-50">
                                            <td class="py-2.5 px-3 font-bold text-slate-900"
                                                x-text="item.product_name"></td>
                                            <td class="py-2.5 px-3 text-slate-500"
                                                x-text="item.original_price_fmt + ' / ' + item.unit_name"></td>

                                            <!-- Persentase Diskon (Format Max 1 Desimal dengan Koma) -->
                                            <td class="py-2.5 px-3 font-bold text-amber-600"
                                                x-text="item.formatted_disc_pct + '%'"></td>

                                            <!-- Minimum Quantity (Format Max 1 Desimal dengan Koma) -->
                                            <td class="py-2.5 px-3 font-bold text-slate-800"
                                                x-text="item.formatted_min_qty + ' ' + item.unit_name"></td>

                                            <td class="py-2.5 px-3 font-extrabold text-abs-green-700"
                                                x-text="item.disc_price_fmt + ' / ' + item.unit_name"></td>
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

    </div>
</x-dashboard-layout>
