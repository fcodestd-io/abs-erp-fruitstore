<x-dashboard-layout title="Manajemen Gudang" active="warehouses">
    <div x-data="{
        modalOpen: {{ session('error') ? 'true' : 'false' }},
        isEdit: false,
        actionUrl: '{{ route('warehouses.store') }}',
        form: { name: '{{ old('name') }}', address: '{{ old('address') }}' },
    
        openCreateModal() {
            this.isEdit = false;
            this.actionUrl = '{{ route('warehouses.store') }}';
            this.form = { name: '', address: '' };
            this.modalOpen = true;
        },
    
        openEditModal(warehouse) {
            this.isEdit = true;
            this.actionUrl = '/warehouses/' + warehouse.id;
            this.form = { name: warehouse.name || '', address: warehouse.address || '' };
            this.modalOpen = true;
        }
    }" class="space-y-6">

        <!-- Header Controls -->
        <div
            class="bg-white p-5 rounded-3xl border border-slate-200/80 shadow-sm flex flex-col sm:flex-row items-center justify-between gap-4">
            <form action="{{ route('warehouses.index') }}" method="GET" class="w-full sm:w-80">
                <div class="relative">
                    <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-400">
                        <i data-lucide="search" class="w-4 h-4"></i>
                    </div>
                    <input type="text" name="search" value="{{ request('search') }}"
                        placeholder="Cari nama atau alamat gudang..."
                        class="block w-full rounded-xl border-0 bg-slate-50 py-2.5 pl-10 pr-4 text-slate-900 ring-1 ring-inset ring-slate-200 placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-abs-green-600 focus:bg-white text-xs transition">
                </div>
            </form>

            <button type="button" @click="openCreateModal()"
                class="w-full sm:w-auto inline-flex items-center justify-center gap-2 rounded-xl bg-abs-green-600 px-4 py-2.5 text-xs font-bold text-white shadow-lg shadow-abs-green-600/20 hover:bg-abs-green-700 transition active:scale-95">
                <i data-lucide="plus" class="w-4 h-4"></i>
                <span>Tambah Gudang</span>
            </button>
        </div>

        <!-- Tabel Gudang -->
        <div class="bg-white rounded-3xl border border-slate-200/80 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead
                        class="bg-slate-50 text-slate-500 uppercase text-[10px] font-extrabold tracking-wider border-b border-slate-100">
                        <tr>
                            <th class="py-3.5 px-5">#</th>
                            <th class="py-3.5 px-5">Nama Gudang</th>
                            <th class="py-3.5 px-5">Alamat</th>
                            <th class="py-3.5 px-5">Supervisor Penanggung Jawab</th>
                            <th class="py-3.5 px-5 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-slate-700">
                        @forelse ($warehouses as $index => $wh)
                            <tr class="hover:bg-slate-50/80 transition">
                                <td class="py-3.5 px-5 font-bold text-slate-400">{{ $warehouses->firstItem() + $index }}
                                </td>
                                <td class="py-3.5 px-5 font-bold text-slate-900">{{ $wh->name }}</td>
                                <td class="py-3.5 px-5 text-slate-500 max-w-xs truncate">{{ $wh->address }}</td>
                                <td class="py-3.5 px-5 font-medium">
                                    @php $spvs = $wh->users->pluck('username')->implode(', '); @endphp
                                    <span
                                        class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-amber-50 text-amber-700 font-bold text-[11px]">
                                        <i data-lucide="user" class="w-3.5 h-3.5"></i>
                                        {{ $spvs ?: 'Belum Ada SPV' }}
                                    </span>
                                </td>
                                <td class="py-3.5 px-5 text-center">
                                    <div class="flex items-center justify-center gap-2">
                                        <!-- Tombol Kelola Gudang -->
                                        <a href="{{ route('warehouses.manage', $wh->id) }}"
                                            class="inline-flex items-center gap-1 px-3 py-1.5 rounded-xl bg-abs-green-50 text-abs-green-700 hover:bg-abs-green-100 font-bold transition text-[11px]">
                                            <i data-lucide="boxes" class="w-3.5 h-3.5"></i>
                                            <span>Kelola Gudang</span>
                                        </a>

                                        <button type="button" @click="openEditModal({{ json_encode($wh) }})"
                                            class="p-2 rounded-lg bg-amber-50 text-amber-600 hover:bg-amber-100 transition"
                                            title="Edit Gudang">
                                            <i data-lucide="pencil" class="w-4 h-4"></i>
                                        </button>

                                        <button type="button"
                                            @click="$dispatch('open-confirm', {
                                                title: 'Hapus Gudang',
                                                message: 'Apakah Anda yakin ingin menghapus gudang {{ addslashes($wh->name) }}?',
                                                confirmText: 'Ya, Hapus',
                                                variant: 'danger',
                                                actionUrl: '{{ route('warehouses.destroy', $wh->id) }}',
                                                actionMethod: 'DELETE'
                                            })"
                                            class="p-2 rounded-lg bg-red-50 text-red-600 hover:bg-red-100 transition"
                                            title="Hapus Gudang">
                                            <i data-lucide="trash-2" class="w-4 h-4"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="py-12 text-center text-slate-400">Belum ada data gudang.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($warehouses->hasPages())
                <div class="p-4 border-t border-slate-100">{{ $warehouses->links() }}</div>
            @endif
        </div>

        <!-- Modal Form Tambah/Edit Gudang -->
        <div x-show="modalOpen" x-cloak class="relative z-50">
            <div x-show="modalOpen" class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm" @click="modalOpen = false">
            </div>
            <div class="fixed inset-0 z-10 overflow-y-auto">
                <div class="flex min-h-full items-center justify-center p-4">
                    <div
                        class="relative w-full max-w-md transform overflow-hidden rounded-3xl bg-white p-6 shadow-2xl transition-all border border-slate-100">
                        <div class="flex items-center justify-between pb-4 border-b border-slate-100">
                            <h3 class="text-base font-bold text-slate-900"
                                x-text="isEdit ? 'Edit Gudang' : 'Tambah Gudang Baru'"></h3>
                            <button @click="modalOpen = false" type="button"
                                class="text-slate-400 hover:text-slate-600"><i data-lucide="x"
                                    class="w-5 h-5"></i></button>
                        </div>
                        <form :action="actionUrl" method="POST" class="mt-4 space-y-4">
                            @csrf
                            <template x-if="isEdit"><input type="hidden" name="_method" value="PUT"></template>
                            <div>
                                <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">Nama
                                    Gudang *</label>
                                <input type="text" name="name" x-model="form.name"
                                    class="block w-full rounded-xl border-0 bg-slate-50 py-2.5 px-3 text-xs text-slate-900 ring-1 ring-inset ring-slate-200 focus:ring-2 focus:ring-abs-green-600"
                                    placeholder="Contoh: Gudang Pusat Cold Storage" required>
                            </div>
                            <div>
                                <label
                                    class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">Alamat
                                    Gudang *</label>
                                <textarea name="address" rows="3" x-model="form.address"
                                    class="block w-full rounded-xl border-0 bg-slate-50 py-2.5 px-3 text-xs text-slate-900 ring-1 ring-inset ring-slate-200 focus:ring-2 focus:ring-abs-green-600"
                                    placeholder="Alamat fisik gudang..." required></textarea>
                            </div>
                            <div class="pt-4 flex items-center justify-end gap-2 border-t border-slate-100">
                                <button type="button" @click="modalOpen = false"
                                    class="rounded-xl px-4 py-2.5 text-xs font-bold text-slate-600 hover:bg-slate-100">Batal</button>
                                <button type="submit"
                                    class="rounded-xl bg-abs-green-600 px-4 py-2.5 text-xs font-bold text-white shadow-lg shadow-abs-green-600/20 hover:bg-abs-green-700"
                                    x-text="isEdit ? 'Simpan Perubahan' : 'Tambah Gudang'"></button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

    </div>
</x-dashboard-layout>
