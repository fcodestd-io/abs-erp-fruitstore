<x-dashboard-layout title="Manajemen Supplier" active="suppliers">
    <div x-data="{
        modalOpen: {{ session('error') ? 'true' : 'false' }},
        isEdit: false,
        actionUrl: '{{ route('suppliers.store') }}',
        form: {
            name: '{{ old('name') }}',
            phone: '{{ old('phone') }}',
            address: '{{ old('address') }}'
        },
    
        openCreateModal() {
            this.isEdit = false;
            this.actionUrl = '{{ route('suppliers.store') }}';
            this.form = { name: '', phone: '', address: '' };
            this.modalOpen = true;
        },
    
        openEditModal(supplier) {
            this.isEdit = true;
            this.actionUrl = '/suppliers/' + supplier.id;
            this.form = {
                name: supplier.name || '',
                phone: supplier.phone || '',
                address: supplier.address || ''
            };
            this.modalOpen = true;
        }
    }" class="space-y-6">

        <!-- Header Controls (Search & Add Button) -->
        <div
            class="bg-white p-5 rounded-3xl border border-slate-200/80 shadow-sm flex flex-col sm:flex-row items-center justify-between gap-4">

            <!-- Form Pencarian -->
            <form action="{{ route('suppliers.index') }}" method="GET" class="w-full sm:w-80">
                <div class="relative">
                    <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-400">
                        <i data-lucide="search" class="w-4 h-4"></i>
                    </div>
                    <input type="text" name="search" value="{{ request('search') }}"
                        placeholder="Cari nama, telepon, atau alamat..."
                        class="block w-full rounded-xl border-0 bg-slate-50 py-2.5 pl-10 pr-4 text-slate-900 ring-1 ring-inset ring-slate-200 placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-abs-green-600 focus:bg-white text-xs transition">
                    @if (request('search'))
                        <a href="{{ route('suppliers.index') }}"
                            class="absolute inset-y-0 right-0 flex items-center pr-3 text-slate-400 hover:text-slate-600 text-xs">
                            <i data-lucide="x" class="w-4 h-4"></i>
                        </a>
                    @endif
                </div>
            </form>

            <!-- Tombol Tambah Supplier -->
            <button type="button" @click="openCreateModal()"
                class="w-full sm:w-auto inline-flex items-center justify-center gap-2 rounded-xl bg-abs-green-600 px-4 py-2.5 text-xs font-bold text-white shadow-lg shadow-abs-green-600/20 hover:bg-abs-green-700 transition active:scale-95">
                <i data-lucide="plus" class="w-4 h-4"></i>
                <span>Tambah Supplier</span>
            </button>
        </div>

        <!-- Tabel Data Supplier -->
        <div class="bg-white rounded-3xl border border-slate-200/80 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead
                        class="bg-slate-50 text-slate-500 uppercase text-[10px] font-extrabold tracking-wider border-b border-slate-100">
                        <tr>
                            <th class="py-3.5 px-5">#</th>
                            <th class="py-3.5 px-5">Nama Supplier</th>
                            <th class="py-3.5 px-5">No. Telepon / WA</th>
                            <th class="py-3.5 px-5">Alamat</th>
                            <th class="py-3.5 px-5 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-slate-700">
                        @forelse ($suppliers as $index => $supplier)
                            <tr class="hover:bg-slate-50/80 transition">
                                <td class="py-3.5 px-5 font-bold text-slate-400">
                                    {{ $suppliers->firstItem() + $index }}
                                </td>
                                <td class="py-3.5 px-5 font-bold text-slate-900">
                                    {{ $supplier->name }}
                                </td>
                                <td class="py-3.5 px-5 font-medium">
                                    {{ $supplier->phone ?? '-' }}
                                </td>
                                <td class="py-3.5 px-5 max-w-xs truncate text-slate-500">
                                    {{ $supplier->address ?? '-' }}
                                </td>
                                <td class="py-3.5 px-5 text-center">
                                    <div class="flex items-center justify-center gap-2">
                                        <!-- Edit Button -->
                                        <button type="button" @click="openEditModal({{ json_encode($supplier) }})"
                                            class="p-2 rounded-lg bg-amber-50 text-amber-600 hover:bg-amber-100 transition"
                                            title="Edit Data">
                                            <i data-lucide="pencil" class="w-4 h-4"></i>
                                        </button>

                                        <!-- Delete Button (Disambungkan ke Confirm Dialog) -->
                                        <button type="button"
                                            @click="$dispatch('open-confirm', {
                                                title: 'Hapus Supplier',
                                                message: 'Apakah Anda yakin ingin menghapus supplier {{ addslashes($supplier->name) }}?',
                                                confirmText: 'Ya, Hapus',
                                                variant: 'danger',
                                                actionUrl: '{{ route('suppliers.destroy', $supplier->id) }}',
                                                actionMethod: 'DELETE'
                                            })"
                                            class="p-2 rounded-lg bg-red-50 text-red-600 hover:bg-red-100 transition"
                                            title="Hapus Supplier">
                                            <i data-lucide="trash-2" class="w-4 h-4"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="py-12 text-center text-slate-400">
                                    Data supplier tidak ditemukan.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination Links -->
            @if ($suppliers->hasPages())
                <div class="p-4 border-t border-slate-100">
                    {{ $suppliers->links() }}
                </div>
            @endif
        </div>

        <!-- Modal Form (Tambah & Edit) -->
        <div x-show="modalOpen" x-cloak class="relative z-50">
            <!-- Backdrop -->
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
                        class="relative w-full max-w-md transform overflow-hidden rounded-3xl bg-white p-6 shadow-2xl transition-all border border-slate-100">
                        <div class="flex items-center justify-between pb-4 border-b border-slate-100">
                            <h3 class="text-base font-bold text-slate-900"
                                x-text="isEdit ? 'Edit Supplier' : 'Tambah Supplier Baru'"></h3>
                            <button @click="modalOpen = false" type="button"
                                class="text-slate-400 hover:text-slate-600">
                                <i data-lucide="x" class="w-5 h-5"></i>
                            </button>
                        </div>

                        <!-- Form Input -->
                        <form :action="actionUrl" method="POST" class="mt-4 space-y-4">
                            @csrf
                            <template x-if="isEdit">
                                <input type="hidden" name="_method" value="PUT">
                            </template>

                            <!-- Nama Supplier -->
                            <div>
                                <label for="name"
                                    class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">
                                    Nama Supplier <span class="text-red-500">*</span>
                                </label>
                                <input type="text" id="name" name="name" x-model="form.name"
                                    class="block w-full rounded-xl border-0 bg-slate-50 py-2.5 px-3 text-slate-900 ring-1 ring-inset ring-slate-200 focus:ring-2 focus:ring-abs-green-600 focus:bg-white text-xs transition"
                                    placeholder="Masukkan nama supplier / PT">
                            </div>

                            <!-- No. Telepon -->
                            <div>
                                <label for="phone"
                                    class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">
                                    No. Telepon / WA
                                </label>
                                <input type="text" id="phone" name="phone" x-model="form.phone"
                                    class="block w-full rounded-xl border-0 bg-slate-50 py-2.5 px-3 text-slate-900 ring-1 ring-inset ring-slate-200 focus:ring-2 focus:ring-abs-green-600 focus:bg-white text-xs transition"
                                    placeholder="Contoh: 081234567890">
                            </div>

                            <!-- Alamat -->
                            <div>
                                <label for="address"
                                    class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">
                                    Alamat Alamat Lengkap
                                </label>
                                <textarea id="address" name="address" rows="3" x-model="form.address"
                                    class="block w-full rounded-xl border-0 bg-slate-50 py-2.5 px-3 text-slate-900 ring-1 ring-inset ring-slate-200 focus:ring-2 focus:ring-abs-green-600 focus:bg-white text-xs transition"
                                    placeholder="Masukkan alamat fisik supplier..."></textarea>
                            </div>

                            <!-- Footer Actions -->
                            <div class="pt-4 flex items-center justify-end gap-2 border-t border-slate-100">
                                <button type="button" @click="modalOpen = false"
                                    class="rounded-xl px-4 py-2.5 text-xs font-bold text-slate-600 hover:bg-slate-100 transition">
                                    Batal
                                </button>
                                <button type="submit"
                                    class="rounded-xl bg-abs-green-600 px-4 py-2.5 text-xs font-bold text-white shadow-lg shadow-abs-green-600/20 hover:bg-abs-green-700 transition"
                                    x-text="isEdit ? 'Simpan Perubahan' : 'Tambah Supplier'">
                                </button>
                            </div>

                        </form>
                    </div>
                </div>
            </div>
        </div>

    </div>
</x-dashboard-layout>
