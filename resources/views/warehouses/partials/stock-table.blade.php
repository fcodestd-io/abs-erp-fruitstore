<div class="bg-white rounded-3xl border border-slate-200/80 shadow-sm overflow-hidden">
    <div class="p-5 border-b border-slate-100 flex flex-col sm:flex-row items-center justify-between gap-4">
        <div>
            <h3 class="text-sm font-bold text-slate-900">Stok Barang Gudang</h3>
            <p class="text-[11px] text-slate-400">Pencarian server-side & pagination</p>
        </div>

        <!-- Form Server-Side Search Stok -->
        <form action="{{ route('warehouses.manage', $warehouse->id) }}" method="GET" class="w-full sm:w-72">
            @if (request('opname_search'))
                <input type="hidden" name="opname_search" value="{{ request('opname_search') }}">
            @endif
            @if (request('opname_status'))
                <input type="hidden" name="opname_status" value="{{ request('opname_status') }}">
            @endif
            @if (request('opname_month'))
                <input type="hidden" name="opname_month" value="{{ request('opname_month') }}">
            @endif
            @if (request('opname_year'))
                <input type="hidden" name="opname_year" value="{{ request('opname_year') }}">
            @endif

            <div class="relative">
                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                    <i data-lucide="search" class="w-3.5 h-3.5"></i>
                </div>
                <input type="text" name="stock_search" value="{{ $stockSearch }}" placeholder="Cari nama produk..."
                    class="block w-full rounded-xl border-0 bg-slate-50 py-2 pl-9 pr-3 text-xs text-slate-900 ring-1 ring-inset ring-slate-200 focus:ring-2 focus:ring-abs-green-600 focus:bg-white">
            </div>
        </form>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-left text-xs">
            <thead
                class="bg-slate-50 text-slate-500 uppercase text-[10px] font-extrabold tracking-wider border-b border-slate-100">
                <tr>
                    <th class="py-3.5 px-5">#</th>
                    <th class="py-3.5 px-5">Nama Produk</th>
                    <th class="py-3.5 px-5">Stok Sistem Gudang</th>
                    <th class="py-3.5 px-5">Satuan Gudang</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 text-slate-700">
                @forelse ($stocks as $index => $stock)
                    <tr class="hover:bg-slate-50/80 transition">
                        <td class="py-3.5 px-5 font-bold text-slate-400">{{ $stocks->firstItem() + $index }}</td>
                        <td class="py-3.5 px-5 font-bold text-slate-900">{{ $stock->product->name ?? '-' }}</td>
                        <td class="py-3.5 px-5 font-extrabold text-slate-800 text-sm">
                            {{ round($stock->stock, 1) == (int) $stock->stock ? number_format($stock->stock, 0, ',', '.') : number_format($stock->stock, 1, ',', '.') }}
                        </td>
                        <td class="py-3.5 px-5">
                            <span
                                class="inline-flex items-center px-2.5 py-1 rounded-md bg-blue-50 text-blue-700 font-bold text-[10px]">
                                {{ $stock->product->warehouseUnit->name ?? '-' }}
                            </span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="py-12 text-center text-slate-400">Data stok produk tidak ditemukan.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($stocks->hasPages())
        <div class="p-4 border-t border-slate-100">{{ $stocks->appends(request()->query())->links() }}</div>
    @endif
</div>
