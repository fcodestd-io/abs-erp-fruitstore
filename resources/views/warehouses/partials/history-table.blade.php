<div class="bg-white rounded-3xl border border-slate-200/80 shadow-sm overflow-hidden space-y-4">
    <!-- Header & Filter Form Server-Side -->
    <div class="p-5 border-b border-slate-100 space-y-3">
        <div class="flex items-center justify-between">
            <h3 class="text-sm font-bold text-slate-900">Riwayat Sesi Stok Opname</h3>
      
        </div>

        <form action="{{ route('warehouses.manage', $warehouse->id) }}" method="GET"
            class="grid grid-cols-1 sm:grid-cols-4 gap-2">
            @if (request('stock_search'))
                <input type="hidden" name="stock_search" value="{{ request('stock_search') }}">
            @endif

            <!-- Search Kode / Note / Operator -->
            <div>
                <input type="text" name="opname_search" value="{{ $opnameSearch }}"
                    placeholder="Kode, Note, Operator..."
                    class="block w-full rounded-xl border-0 bg-slate-50 py-2 px-3 text-xs text-slate-900 ring-1 ring-inset ring-slate-200 focus:ring-2 focus:ring-abs-green-600">
            </div>

            <!-- Filter Status -->
            <div>
                <select name="opname_status"
                    class="block w-full rounded-xl border-0 bg-slate-50 py-2 px-3 text-xs text-slate-900 ring-1 ring-inset ring-slate-200 focus:ring-2 focus:ring-abs-green-600">
                    <option value="">-- Semua Status --</option>
                    <option value="started" {{ $opnameStatus === 'started' ? 'selected' : '' }}>STARTED / DRAFT</option>
                    <option value="completed" {{ $opnameStatus === 'completed' ? 'selected' : '' }}>COMPLETED</option>
                    <option value="canceled" {{ $opnameStatus === 'canceled' ? 'selected' : '' }}>CANCELED</option>
                </select>
            </div>

            <!-- Filter Bulan -->
            <div>
                <select name="opname_month"
                    class="block w-full rounded-xl border-0 bg-slate-50 py-2 px-3 text-xs text-slate-900 ring-1 ring-inset ring-slate-200 focus:ring-2 focus:ring-abs-green-600">
                    <option value="">-- Semua Bulan --</option>
                    @for ($m = 1; $m <= 12; $m++)
                        <option value="{{ $m }}" {{ $opnameMonth == $m ? 'selected' : '' }}>
                            {{ DateTime::createFromFormat('!m', $m)->format('F') }}
                        </option>
                    @endfor
                </select>
            </div>

            <!-- Filter Tahun & Submit -->
            <div class="flex gap-2">
                <select name="opname_year"
                    class="block w-full rounded-xl border-0 bg-slate-50 py-2 px-3 text-xs text-slate-900 ring-1 ring-inset ring-slate-200 focus:ring-2 focus:ring-abs-green-600">
                    <option value="">-- Tahun --</option>
                    @for ($y = date('Y'); $y >= date('Y') - 3; $y--)
                        <option value="{{ $y }}" {{ $opnameYear == $y ? 'selected' : '' }}>
                            {{ $y }}</option>
                    @endfor
                </select>
                <button type="submit"
                    class="px-3 py-2 bg-slate-800 text-white rounded-xl text-xs font-bold hover:bg-slate-900 transition">Filter</button>
            </div>
        </form>
    </div>

    <!-- Tabel Data Riwayat -->
    <div class="overflow-x-auto">
        <table class="w-full text-left text-xs">
            <thead
                class="bg-slate-50 text-slate-500 uppercase text-[10px] font-extrabold tracking-wider border-b border-slate-100">
                <tr>
                    <th class="py-3.5 px-5">Kode / Sesi</th>
                    <th class="py-3.5 px-5">Operator</th>
                    <th class="py-3.5 px-5">Status Opname</th>
                    <th class="py-3.5 px-5">Waktu Dimulai</th>
                    <th class="py-3.5 px-5">Waktu Dibatalkan / Selesai</th>
                    <th class="py-3.5 px-5 text-center">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 text-slate-700">
                @forelse ($opnameHistory as $adj)
                    <tr class="hover:bg-slate-50/80 transition">
                        <td class="py-3.5 px-5 font-bold text-slate-900">
                            {{ $adj->code }}
                            @if ($adj->note)
                                <p class="text-[10px] font-normal text-slate-400 truncate max-w-xs">{{ $adj->note }}
                                </p>
                            @endif
                        </td>
                        <td class="py-3.5 px-5 font-medium">{{ $adj->operator->username ?? '-' }}</td>
                        <td class="py-3.5 px-5">
                            @if (!$adj->completed_at && !$adj->canceled_at)
                                <span
                                    class="inline-flex items-center px-2.5 py-1 rounded-lg bg-amber-100 text-amber-800 font-extrabold text-[10px] uppercase">●
                                    STARTED / DRAFT</span>
                            @elseif($adj->completed_at)
                                <span
                                    class="inline-flex items-center px-2.5 py-1 rounded-lg bg-emerald-100 text-emerald-800 font-extrabold text-[10px] uppercase">✓
                                    COMPLETED</span>
                            @elseif($adj->canceled_at)
                                <span
                                    class="inline-flex items-center px-2.5 py-1 rounded-lg bg-red-100 text-red-800 font-extrabold text-[10px] uppercase">✕
                                    CANCELED</span>
                            @endif
                        </td>
                        <td class="py-3.5 px-5 text-slate-500">{{ $adj->created_at->format('d M Y, H:i') }}</td>
                        <td class="py-3.5 px-5 text-slate-500">
                            @if ($adj->completed_at)
                                <span
                                    class="text-emerald-700 font-bold">{{ $adj->completed_at->format('d M Y, H:i') }}</span>
                            @elseif($adj->canceled_at)
                                <span
                                    class="text-red-600 font-bold">{{ $adj->canceled_at->format('d M Y, H:i') }}</span>
                            @else
                                -
                            @endif
                        </td>
                        <td class="py-3.5 px-5 text-center">
                            <div class="flex items-center justify-center gap-1.5">
                                <!-- Tombol Lihat Items (AJAX Modal) -->
                                <button type="button" @click="openDetailModal({{ $adj->id }})"
                                    class="p-1.5 rounded-lg bg-slate-100 text-slate-700 hover:bg-slate-200 font-bold text-[11px] transition"
                                    title="Lihat Rincian Barang">
                                    <i data-lucide="eye" class="w-3.5 h-3.5"></i>
                                </button>

                                @if (!$adj->completed_at && !$adj->canceled_at)
                                    <button type="button" @click="opnameModal = true"
                                        class="px-2.5 py-1 rounded-lg bg-amber-50 text-amber-700 hover:bg-amber-100 font-bold text-[11px]">Lanjut</button>
                                    <button type="button"
                                        @click="$dispatch('open-confirm', {
                                            title: 'Batalkan Opname',
                                            message: 'Apakah Anda yakin ingin membatalkan opname {{ $adj->code }}?',
                                            confirmText: 'Ya, Batalkan',
                                            variant: 'danger',
                                            actionUrl: '{{ route('warehouses.opname.cancel', [$warehouse->id, $adj->id]) }}',
                                            actionMethod: 'POST'
                                        })"
                                        class="px-2.5 py-1 rounded-lg bg-red-50 text-red-600 hover:bg-red-100 font-bold text-[11px]">Batalkan</button>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="py-12 text-center text-slate-400">Data riwayat opname tidak ditemukan.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($opnameHistory->hasPages())
        <div class="p-4 border-t border-slate-100">{{ $opnameHistory->appends(request()->query())->links() }}</div>
    @endif
</div>
