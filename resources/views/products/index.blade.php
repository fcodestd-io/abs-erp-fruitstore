<x-dashboard-layout title="Manajemen Produk" active="products">
    <div x-data="{
        modalOpen: {{ session('error') ? 'true' : 'false' }},
        isEdit: false,
        actionUrl: '{{ route('products.store') }}',
        form: {
            name: '{{ old('name') }}',
            cost_price: '{{ old('cost_price') }}',
            selling_price: '{{ old('selling_price') }}',
            store_unit_id: '{{ old('store_unit_id') }}',
            warehouse_unit_id: '{{ old('warehouse_unit_id') }}'
        },
    
        // Data & State Inline CRUD Satuan
        units: {{ Js::from($units) }},
        showUnitManager: false,
        newUnitName: '',
        unitErrorMessage: '',
    
        async addUnit() {
            this.unitErrorMessage = '';
            if (!this.newUnitName.trim()) {
                this.unitErrorMessage = 'Nama satuan tidak boleh kosong!';
                return;
            }
    
            try {
                let response = await fetch('{{ route('units.store') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ name: this.newUnitName })
                });
    
                let data = await response.json();
                if (response.ok) {
                    this.units = data.units;
                    this.newUnitName = '';
                    Toast.fire({ icon: 'success', title: data.message });
                } else {
                    this.unitErrorMessage = data.message;
                }
            } catch (err) {
                this.unitErrorMessage = 'Gagal menambahkan satuan.';
            }
        },
    
        async deleteUnit(unitId) {
            if (!confirm('Apakah Anda yakin ingin menghapus satuan ini?')) return;
            this.unitErrorMessage = '';
    
            try {
                let response = await fetch('/units-delete/' + unitId, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    }
                });
    
                let data = await response.json();
                if (response.ok) {
                    this.units = data.units;
                    if (this.form.store_unit_id == unitId) this.form.store_unit_id = '';
                    if (this.form.warehouse_unit_id == unitId) this.form.warehouse_unit_id = '';
                    Toast.fire({ icon: 'success', title: data.message });
                } else {
                    this.unitErrorMessage = data.message;
                }
            } catch (err) {
                this.unitErrorMessage = 'Gagal menghapus satuan.';
            }
        },
    
        openCreateModal() {
            this.isEdit = false;
            this.actionUrl = '{{ route('products.store') }}';
            this.form = { name: '', cost_price: '', selling_price: '', store_unit_id: '', warehouse_unit_id: '' };
            this.showUnitManager = false;
            this.modalOpen = true;
        },
    
        openEditModal(product) {
            this.isEdit = true;
            this.actionUrl = '/products/' + product.id;
            this.form = {
                name: product.name || '',
                cost_price: product.cost_price || '',
                selling_price: product.selling_price || '',
                store_unit_id: product.store_unit_id || '',
                warehouse_unit_id: product.warehouse_unit_id || ''
            };
            this.showUnitManager = false;
            this.modalOpen = true;
        }
    }" class="space-y-6">

        <!-- Header Controls (Search & Add Button) -->
        <div
            class="bg-white p-5 rounded-3xl border border-slate-200/80 shadow-sm flex flex-col sm:flex-row items-center justify-between gap-4">

            <form action="{{ route('products.index') }}" method="GET" class="w-full sm:w-80">
                <div class="relative">
                    <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-400">
                        <i data-lucide="search" class="w-4 h-4"></i>
                    </div>
                    <input type="text" name="search" value="{{ request('search') }}"
                        placeholder="Cari nama produk..."
                        class="block w-full rounded-xl border-0 bg-slate-50 py-2.5 pl-10 pr-4 text-slate-900 ring-1 ring-inset ring-slate-200 placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-abs-green-600 focus:bg-white text-xs transition">
                    @if (request('search'))
                        <a href="{{ route('products.index') }}"
                            class="absolute inset-y-0 right-0 flex items-center pr-3 text-slate-400 hover:text-slate-600 text-xs">
                            <i data-lucide="x" class="w-4 h-4"></i>
                        </a>
                    @endif
                </div>
            </form>

            <button type="button" @click="openCreateModal()"
                class="w-full sm:w-auto inline-flex items-center justify-center gap-2 rounded-xl bg-abs-green-600 px-4 py-2.5 text-xs font-bold text-white shadow-lg shadow-abs-green-600/20 hover:bg-abs-green-700 transition active:scale-95">
                <i data-lucide="plus" class="w-4 h-4"></i>
                <span>Tambah Produk</span>
            </button>
        </div>

        <!-- Tabel Data Produk -->
        <div class="bg-white rounded-3xl border border-slate-200/80 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead
                        class="bg-slate-50 text-slate-500 uppercase text-[10px] font-extrabold tracking-wider border-b border-slate-100">
                        <tr>
                            <th class="py-3.5 px-5">#</th>
                            <th class="py-3.5 px-5">Nama Produk</th>
                            <th class="py-3.5 px-5">Harga Pokok</th>
                            <th class="py-3.5 px-5">Harga Jual</th>
                            <th class="py-3.5 px-5">Satuan Toko</th>
                            <th class="py-3.5 px-5">Satuan Gudang</th>
                            <th class="py-3.5 px-5 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-slate-700">
                        @forelse ($products as $index => $product)
                            <tr class="hover:bg-slate-50/80 transition">
                                <td class="py-3.5 px-5 font-bold text-slate-400">
                                    {{ $products->firstItem() + $index }}
                                </td>
                                <td class="py-3.5 px-5 font-bold text-slate-900">
                                    {{ $product->name }}
                                </td>
                                <td class="py-3.5 px-5 font-medium text-slate-600">
                                    Rp {{ number_format($product->cost_price, 0, ',', '.') }}<span
                                        class="text-slate-400 text-[11px]">/{{ $product->storeUnit->name ?? 'satuan' }}</span>
                                </td>
                                <td class="py-3.5 px-5 font-bold text-abs-green-700">
                                    Rp {{ number_format($product->selling_price, 0, ',', '.') }}<span
                                        class="text-abs-green-600/70 font-normal text-[11px]">/{{ $product->storeUnit->name ?? 'satuan' }}</span>
                                </td>
                                <td class="py-3.5 px-5">
                                    <span
                                        class="inline-flex items-center px-2 py-0.5 rounded bg-slate-100 text-slate-700 font-bold text-[10px]">
                                        {{ $product->storeUnit->name ?? '-' }}
                                    </span>
                                </td>
                                <td class="py-3.5 px-5">
                                    <span
                                        class="inline-flex items-center px-2 py-0.5 rounded bg-blue-50 text-blue-700 font-bold text-[10px]">
                                        {{ $product->warehouseUnit->name ?? '-' }}
                                    </span>
                                </td>
                                <td class="py-3.5 px-5 text-center">
                                    <div class="flex items-center justify-center gap-2">
                                        <button type="button" @click="openEditModal({{ json_encode($product) }})"
                                            class="p-2 rounded-lg bg-amber-50 text-amber-600 hover:bg-amber-100 transition"
                                            title="Edit Produk">
                                            <i data-lucide="pencil" class="w-4 h-4"></i>
                                        </button>

                                        <button type="button"
                                            @click="$dispatch('open-confirm', {
                                                title: 'Hapus Produk',
                                                message: 'Apakah Anda yakin ingin menghapus {{ addslashes($product->name) }}?',
                                                confirmText: 'Ya, Hapus',
                                                variant: 'danger',
                                                actionUrl: '{{ route('products.destroy', $product->id) }}',
                                                actionMethod: 'DELETE'
                                            })"
                                            class="p-2 rounded-lg bg-red-50 text-red-600 hover:bg-red-100 transition"
                                            title="Hapus Produk">
                                            <i data-lucide="trash-2" class="w-4 h-4"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="py-12 text-center text-slate-400">
                                    Data produk tidak ditemukan.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($products->hasPages())
                <div class="p-4 border-t border-slate-100">
                    {{ $products->links() }}
                </div>
            @endif
        </div>

        <!-- Modal Form (Tambah & Edit Produk) -->
        <div x-show="modalOpen" x-cloak class="relative z-50">
            <div x-show="modalOpen" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200"
                x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm" @click="modalOpen = false"></div>

            <div class="fixed inset-0 z-10 overflow-y-auto">
                <div class="flex min-h-full items-center justify-center p-4">
                    <div x-show="modalOpen" x-transition:enter="ease-out duration-300"
                        x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
                        x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100 scale-100"
                        x-transition:leave-end="opacity-0 scale-95"
                        class="relative w-full max-w-lg transform overflow-hidden rounded-3xl bg-white p-6 shadow-2xl transition-all border border-slate-100">
                        <div class="flex items-center justify-between pb-4 border-b border-slate-100">
                            <h3 class="text-base font-bold text-slate-900"
                                x-text="isEdit ? 'Edit Produk' : 'Tambah Produk Baru'"></h3>
                            <button @click="modalOpen = false" type="button"
                                class="text-slate-400 hover:text-slate-600">
                                <i data-lucide="x" class="w-5 h-5"></i>
                            </button>
                        </div>

                        <!-- Form Input Produk -->
                        <form :action="actionUrl" method="POST" class="mt-4 space-y-4">
                            @csrf
                            <template x-if="isEdit">
                                <input type="hidden" name="_method" value="PUT">
                            </template>

                            <!-- Nama Produk -->
                            <div>
                                <label for="name"
                                    class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">
                                    Nama Produk <span class="text-red-500">*</span>
                                </label>
                                <input type="text" id="name" name="name" x-model="form.name"
                                    class="block w-full rounded-xl border-0 bg-slate-50 py-2.5 px-3 text-slate-900 ring-1 ring-inset ring-slate-200 focus:ring-2 focus:ring-abs-green-600 focus:bg-white text-xs transition"
                                    placeholder="Contoh: Apel Fuji Super">
                            </div>

                            <!-- Grid Harga Pokok & Harga Jual -->
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label for="cost_price"
                                        class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">
                                        Harga Pokok (Rp) <span class="text-red-500">*</span>
                                    </label>
                                    <input type="number" id="cost_price" name="cost_price"
                                        x-model="form.cost_price"
                                        class="block w-full rounded-xl border-0 bg-slate-50 py-2.5 px-3 text-slate-900 ring-1 ring-inset ring-slate-200 focus:ring-2 focus:ring-abs-green-600 focus:bg-white text-xs transition"
                                        placeholder="0">
                                </div>

                                <div>
                                    <label for="selling_price"
                                        class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">
                                        Harga Jual (Rp) <span class="text-red-500">*</span>
                                    </label>
                                    <input type="number" id="selling_price" name="selling_price"
                                        x-model="form.selling_price"
                                        class="block w-full rounded-xl border-0 bg-slate-50 py-2.5 px-3 text-slate-900 ring-1 ring-inset ring-slate-200 focus:ring-2 focus:ring-abs-green-600 focus:bg-white text-xs transition"
                                        placeholder="0">
                                </div>
                            </div>

                            <!-- Section Satuan Toko & Satuan Gudang dengan Toggle Manager Satuan -->
                            <div class="pt-2">
                                <div class="flex items-center justify-between mb-2">
                                    <span class="text-xs font-bold uppercase tracking-wider text-slate-700">Pilihan
                                        Satuan</span>

                                    <button type="button" @click="showUnitManager = !showUnitManager"
                                        class="text-[11px] font-bold text-abs-green-600 hover:text-abs-green-700 flex items-center gap-1 bg-abs-green-50 px-2 py-1 rounded-lg transition">
                                        <i data-lucide="settings-2" class="w-3.5 h-3.5"></i>
                                        <span
                                            x-text="showUnitManager ? 'Tutup Pengelola Satuan' : '+ Kelola Master Satuan'"></span>
                                    </button>
                                </div>

                                <!-- PANEL INLINE CRUD SATUAN -->
                                <div x-show="showUnitManager" x-collapse
                                    class="p-3.5 rounded-2xl bg-slate-100 border border-slate-200 space-y-3 mb-3">
                                    <p class="text-[10px] font-bold uppercase text-slate-500 tracking-wider">Quick
                                        Manager Master Satuan</p>

                                    <div class="flex gap-2">
                                        <input type="text" x-model="newUnitName"
                                            @keydown.enter.prevent="addUnit()"
                                            placeholder="Nama Satuan Baru (misal: Kg, Dus, Pcs)"
                                            class="flex-1 rounded-xl border-0 bg-white py-2 px-3 text-xs text-slate-900 ring-1 ring-inset ring-slate-200 focus:ring-2 focus:ring-abs-green-600">
                                        <button type="button" @click="addUnit()"
                                            class="px-3 py-2 bg-abs-green-600 hover:bg-abs-green-700 text-white rounded-xl text-xs font-bold transition active:scale-95">
                                            Tambah
                                        </button>
                                    </div>

                                    <p x-show="unitErrorMessage" class="text-[11px] font-bold text-red-600"
                                        x-text="unitErrorMessage"></p>

                                    <div class="flex flex-wrap gap-1.5 pt-1 max-h-28 overflow-y-auto">
                                        <template x-for="unit in units" :key="unit.id">
                                            <span
                                                class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-white border border-slate-200 text-[11px] font-bold text-slate-700 shadow-sm">
                                                <span x-text="unit.name"></span>
                                                <button type="button" @click="deleteUnit(unit.id)"
                                                    class="text-slate-400 hover:text-red-600 transition"
                                                    title="Hapus Satuan">
                                                    <i data-lucide="x-circle" class="w-3.5 h-3.5"></i>
                                                </button>
                                            </span>
                                        </template>
                                    </div>
                                </div>

                                <!-- Grid Dropdown Satuan Toko & Satuan Gudang -->
                                <div class="grid grid-cols-2 gap-3">
                                    <div>
                                        <label for="store_unit_id"
                                            class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">
                                            Satuan Toko (Eceran) <span class="text-red-500">*</span>
                                        </label>
                                        <select id="store_unit_id" name="store_unit_id" x-model="form.store_unit_id"
                                            class="block w-full rounded-xl border-0 bg-slate-50 py-2.5 px-3 text-slate-900 ring-1 ring-inset ring-slate-200 focus:ring-2 focus:ring-abs-green-600 focus:bg-white text-xs transition">
                                            <option value="">-- Pilih Satuan --</option>
                                            <template x-for="u in units" :key="'store-' + u.id">
                                                <option :value="u.id" x-text="u.name"
                                                    :selected="form.store_unit_id == u.id"></option>
                                            </template>
                                        </select>
                                    </div>

                                    <div>
                                        <label for="warehouse_unit_id"
                                            class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">
                                            Satuan Gudang (Grosir) <span class="text-red-500">*</span>
                                        </label>
                                        <select id="warehouse_unit_id" name="warehouse_unit_id"
                                            x-model="form.warehouse_unit_id"
                                            class="block w-full rounded-xl border-0 bg-slate-50 py-2.5 px-3 text-slate-900 ring-1 ring-inset ring-slate-200 focus:ring-2 focus:ring-abs-green-600 focus:bg-white text-xs transition">
                                            <option value="">-- Pilih Satuan --</option>
                                            <template x-for="u in units" :key="'wh-' + u.id">
                                                <option :value="u.id" x-text="u.name"
                                                    :selected="form.warehouse_unit_id == u.id"></option>
                                            </template>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <!-- Footer Actions -->
                            <div class="pt-4 flex items-center justify-end gap-2 border-t border-slate-100">
                                <button type="button" @click="modalOpen = false"
                                    class="rounded-xl px-4 py-2.5 text-xs font-bold text-slate-600 hover:bg-slate-100 transition">
                                    Batal
                                </button>
                                <button type="submit"
                                    class="rounded-xl bg-abs-green-600 px-4 py-2.5 text-xs font-bold text-white shadow-lg shadow-abs-green-600/20 hover:bg-abs-green-700 transition"
                                    x-text="isEdit ? 'Simpan Perubahan' : 'Tambah Produk'">
                                </button>
                            </div>

                        </form>
                    </div>
                </div>
            </div>
        </div>

    </div>
</x-dashboard-layout>
