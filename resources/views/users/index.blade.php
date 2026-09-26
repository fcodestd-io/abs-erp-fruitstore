<x-dashboard-layout title="Manajemen Pengguna" active="users">
    <div x-data="{
        modalOpen: {{ session('error') ? 'true' : 'false' }},
        isEdit: false,
        isOwner: false,
        actionUrl: '{{ route('users.store') }}',
        form: {
            id: null,
            username: '{{ old('username') }}',
            password: '',
            role: '{{ old('role', 'admin') }}',
            warehouse_id: '{{ old('warehouse_id', '') }}',
            store_id: '{{ old('store_id', '') }}'
        },
    
        openCreateModal() {
            this.isEdit = false;
            this.isOwner = false;
            this.actionUrl = '{{ route('users.store') }}';
            this.form = { id: null, username: '', password: '', role: 'admin', warehouse_id: '', store_id: '' };
            this.modalOpen = true;
        },
    
        openEditModal(user) {
            this.isEdit = true;
            this.isOwner = (user.role === 'owner');
            this.actionUrl = '/users/' + user.id;
            this.form = {
                id: user.id,
                username: user.username || '',
                password: '',
                role: user.role || 'admin',
                warehouse_id: user.warehouse_id || '',
                store_id: user.store_id || ''
            };
            this.modalOpen = true;
        }
    }" class="space-y-6">

        <!-- Header Controls (Search & Add Button) -->
        <div
            class="bg-white p-5 rounded-3xl border border-slate-200/80 shadow-sm flex flex-col sm:flex-row items-center justify-between gap-4">

            <!-- Form Pencarian -->
            <form action="{{ route('users.index') }}" method="GET" class="w-full sm:w-80">
                <div class="relative">
                    <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-400">
                        <i data-lucide="search" class="w-4 h-4"></i>
                    </div>
                    <input type="text" name="search" value="{{ request('search') }}"
                        placeholder="Cari username atau role..."
                        class="block w-full rounded-xl border-0 bg-slate-50 py-2.5 pl-10 pr-4 text-slate-900 ring-1 ring-inset ring-slate-200 placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-abs-green-600 focus:bg-white text-xs transition">
                    @if (request('search'))
                        <a href="{{ route('users.index') }}"
                            class="absolute inset-y-0 right-0 flex items-center pr-3 text-slate-400 hover:text-slate-600 text-xs">
                            <i data-lucide="x" class="w-4 h-4"></i>
                        </a>
                    @endif
                </div>
            </form>

            <!-- Tombol Tambah Pengguna -->
            <button type="button" @click="openCreateModal()"
                class="w-full sm:w-auto inline-flex items-center justify-center gap-2 rounded-xl bg-abs-green-600 px-4 py-2.5 text-xs font-bold text-white shadow-lg shadow-abs-green-600/20 hover:bg-abs-green-700 transition active:scale-95">
                <i data-lucide="user-plus" class="w-4 h-4"></i>
                <span>Tambah Pengguna</span>
            </button>
        </div>

        <!-- Tabel Data Users -->
        <div class="bg-white rounded-3xl border border-slate-200/80 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead
                        class="bg-slate-50 text-slate-500 uppercase text-[10px] font-extrabold tracking-wider border-b border-slate-100">
                        <tr>
                            <th class="py-3.5 px-5">#</th>
                            <th class="py-3.5 px-5">Username</th>
                            <th class="py-3.5 px-5">Role</th>
                            <th class="py-3.5 px-5">Penempatan (Gudang / Toko)</th>
                            <th class="py-3.5 px-5 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-slate-700">
                        @forelse ($users as $index => $user)
                            <tr class="hover:bg-slate-50/80 transition">
                                <td class="py-3.5 px-5 font-bold text-slate-400">
                                    {{ $users->firstItem() + $index }}
                                </td>
                                <td class="py-3.5 px-5 font-bold text-slate-900">
                                    {{ $user->username }}
                                </td>
                                <td class="py-3.5 px-5">
                                    <span
                                        class="inline-flex items-center px-2.5 py-1 rounded-lg text-[10px] font-extrabold uppercase tracking-wider
                                        {{ $user->role === 'owner' ? 'bg-purple-100 text-purple-700' : '' }}
                                        {{ $user->role === 'admin' ? 'bg-blue-100 text-blue-700' : '' }}
                                        {{ $user->role === 'warehouse_supervisor' ? 'bg-amber-100 text-amber-700' : '' }}
                                        {{ $user->role === 'cashier' ? 'bg-emerald-100 text-emerald-700' : '' }}
                                    ">
                                        {{ str_replace('_', ' ', $user->role) }}
                                    </span>
                                </td>
                                <td class="py-3.5 px-5 font-medium text-slate-600">
                                    @if ($user->role === 'warehouse_supervisor')
                                        <span class="inline-flex items-center gap-1">
                                            <i data-lucide="warehouse" class="w-3.5 h-3.5 text-slate-400"></i>
                                            {{ $user->warehouse->name ?? '-' }}
                                        </span>
                                    @elseif($user->role === 'cashier')
                                        <span class="inline-flex items-center gap-1">
                                            <i data-lucide="store" class="w-3.5 h-3.5 text-slate-400"></i>
                                            {{ $user->store->name ?? '-' }}
                                        </span>
                                    @else
                                        <span class="text-slate-400">- (Akses Penuh)</span>
                                    @endif
                                </td>
                                <td class="py-3.5 px-5 text-center">
                                    <div class="flex items-center justify-center gap-2">
                                        <!-- Edit Button -->
                                        <button type="button" @click="openEditModal({{ json_encode($user) }})"
                                            class="p-2 rounded-lg bg-amber-50 text-amber-600 hover:bg-amber-100 transition"
                                            title="Edit User">
                                            <i data-lucide="pencil" class="w-4 h-4"></i>
                                        </button>

                                        <!-- Delete Button (Owner tidak boleh dihapus) -->
                                        @if ($user->role !== 'owner')
                                            <button type="button"
                                                @click="$dispatch('open-confirm', {
                                                    title: 'Hapus User',
                                                    message: 'Apakah Anda yakin ingin menghapus user {{ addslashes($user->username) }}?',
                                                    confirmText: 'Ya, Hapus',
                                                    variant: 'danger',
                                                    actionUrl: '{{ route('users.destroy', $user->id) }}',
                                                    actionMethod: 'DELETE'
                                                })"
                                                class="p-2 rounded-lg bg-red-50 text-red-600 hover:bg-red-100 transition"
                                                title="Hapus User">
                                                <i data-lucide="trash-2" class="w-4 h-4"></i>
                                            </button>
                                        @else
                                            <span class="p-2 rounded-lg bg-slate-100 text-slate-300 cursor-not-allowed"
                                                title="Owner Utama Tidak Dapat Dihapus">
                                                <i data-lucide="lock" class="w-4 h-4"></i>
                                            </span>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="py-12 text-center text-slate-400">
                                    Data pengguna tidak ditemukan.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination Links -->
            @if ($users->hasPages())
                <div class="p-4 border-t border-slate-100">
                    {{ $users->links() }}
                </div>
            @endif
        </div>

        <!-- Modal Form (Tambah & Edit User) -->
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
                        class="relative w-full max-w-md transform overflow-hidden rounded-3xl bg-white p-6 shadow-2xl transition-all border border-slate-100">
                        <div class="flex items-center justify-between pb-4 border-b border-slate-100">
                            <h3 class="text-base font-bold text-slate-900"
                                x-text="isOwner ? 'Edit Akun Owner' : (isEdit ? 'Edit Pengguna' : 'Tambah Pengguna Baru')">
                            </h3>
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

                            <!-- Username Field -->
                            <div>
                                <label for="username"
                                    class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">
                                    Username <span class="text-red-500">*</span>
                                </label>
                                <input type="text" id="username" name="username" x-model="form.username"
                                    class="block w-full rounded-xl border-0 bg-slate-50 py-2.5 px-3 text-slate-900 ring-1 ring-inset ring-slate-200 focus:ring-2 focus:ring-abs-green-600 focus:bg-white text-xs transition"
                                    placeholder="Masukkan username">
                            </div>

                            <!-- Password Field -->
                            <div>
                                <label for="password"
                                    class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">
                                    Password <span x-show="!isEdit" class="text-red-500">*</span>
                                    <span x-show="isEdit" class="text-slate-400 font-normal lowercase">(Kosongkan jika
                                        tidak diubah)</span>
                                </label>
                                <input type="password" id="password" name="password" x-model="form.password"
                                    class="block w-full rounded-xl border-0 bg-slate-50 py-2.5 px-3 text-slate-900 ring-1 ring-inset ring-slate-200 focus:ring-2 focus:ring-abs-green-600 focus:bg-white text-xs transition"
                                    placeholder="••••••••">
                            </div>

                            <!-- Role Select (TIDAK ADA pilihan Owner) -->
                            <template x-if="!isOwner">
                                <div>
                                    <label for="role"
                                        class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">
                                        Role / Hak Akses <span class="text-red-500">*</span>
                                    </label>
                                    <select id="role" name="role" x-model="form.role"
                                        class="block w-full rounded-xl border-0 bg-slate-50 py-2.5 px-3 text-slate-900 ring-1 ring-inset ring-slate-200 focus:ring-2 focus:ring-abs-green-600 focus:bg-white text-xs transition">
                                        <option value="admin">Admin</option>
                                        <option value="warehouse_supervisor">Supervisor Gudang</option>
                                        <option value="cashier">Kasir Toko</option>
                                    </select>
                                </div>
                            </template>

                            <!-- Pilihan Gudang Dinamis (Hanya untuk Supervisor Gudang) -->
                            <div x-show="!isOwner && form.role === 'warehouse_supervisor'" x-cloak>
                                <label for="warehouse_id"
                                    class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">
                                    Pilih Gudang <span class="text-red-500">*</span>
                                </label>
                                <select id="warehouse_id" name="warehouse_id" x-model="form.warehouse_id"
                                    class="block w-full rounded-xl border-0 bg-slate-50 py-2.5 px-3 text-slate-900 ring-1 ring-inset ring-slate-200 focus:ring-2 focus:ring-abs-green-600 focus:bg-white text-xs transition">
                                    <option value="">-- Pilih Gudang --</option>
                                    @foreach ($warehouses as $wh)
                                        <option value="{{ $wh->id }}">{{ $wh->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Pilihan Toko Dinamis (Hanya untuk Kasir) -->
                            <div x-show="!isOwner && form.role === 'cashier'" x-cloak>
                                <label for="store_id"
                                    class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">
                                    Pilih Toko Cabang <span class="text-red-500">*</span>
                                </label>
                                <select id="store_id" name="store_id" x-model="form.store_id"
                                    class="block w-full rounded-xl border-0 bg-slate-50 py-2.5 px-3 text-slate-900 ring-1 ring-inset ring-slate-200 focus:ring-2 focus:ring-abs-green-600 focus:bg-white text-xs transition">
                                    <option value="">-- Pilih Toko --</option>
                                    @foreach ($stores as $st)
                                        <option value="{{ $st->id }}">{{ $st->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Footer Actions -->
                            <div class="pt-4 flex items-center justify-end gap-2 border-t border-slate-100">
                                <button type="button" @click="modalOpen = false"
                                    class="rounded-xl px-4 py-2.5 text-xs font-bold text-slate-600 hover:bg-slate-100 transition">
                                    Batal
                                </button>
                                <button type="submit"
                                    class="rounded-xl bg-abs-green-600 px-4 py-2.5 text-xs font-bold text-white shadow-lg shadow-abs-green-600/20 hover:bg-abs-green-700 transition"
                                    x-text="isEdit ? 'Simpan Perubahan' : 'Tambah Pengguna'">
                                </button>
                            </div>

                        </form>
                    </div>
                </div>
            </div>
        </div>

    </div>
</x-dashboard-layout>
