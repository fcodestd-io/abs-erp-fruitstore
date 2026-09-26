<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\StockAdjustment;
use App\Models\StockAdjustmentItem;
use App\Models\Store;
use App\Models\StoreStock;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class StoreController extends Controller
{
    // 1. Index CRUD Toko
    public function index(Request $request)
    {
        $search = $request->query('search');

        $stores = Store::with(['users' => function ($q) {
            $q->where('role', 'cashier');
        }])
            ->when($search, fn ($q) => $q->where('name', 'like', "%{$search}%")->orWhere('address', 'like', "%{$search}%"))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('stores.index', compact('stores', 'search'));
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:255'],
            'address' => ['required', 'string'],
        ], [
            'name.required' => 'Nama toko wajib diisi.',
            'address.required' => 'Alamat toko wajib diisi.',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withInput()->with('error', $validator->errors()->first());
        }

        try {
            Store::create($validator->validated());

            return redirect()->route('stores.index')->with('success', 'Toko berhasil ditambahkan.');
        } catch (Exception $e) {
            return redirect()->back()->withInput()->with('error', 'Gagal menambahkan toko: '.$e->getMessage());
        }
    }

    public function update(Request $request, Store $store)
    {
        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:255'],
            'address' => ['required', 'string'],
        ], [
            'name.required' => 'Nama toko wajib diisi.',
            'address.required' => 'Alamat toko wajib diisi.',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withInput()->with('error', $validator->errors()->first());
        }

        try {
            $store->update($validator->validated());

            return redirect()->route('stores.index')->with('success', 'Data toko berhasil diperbarui.');
        } catch (Exception $e) {
            return redirect()->back()->withInput()->with('error', 'Gagal memperbarui toko: '.$e->getMessage());
        }
    }

    public function destroy(Store $store)
    {
        try {
            $store->delete();

            return redirect()->route('stores.index')->with('success', 'Toko berhasil dihapus.');
        } catch (Exception $e) {
            return redirect()->route('stores.index')->with('error', 'Gagal menghapus toko: '.$e->getMessage());
        }
    }

    // 2. Halaman Kelola Toko
    public function manage(Request $request, Store $store)
    {
        $store->load(['users' => fn ($q) => $q->where('role', 'cashier')]);
        $cashierNames = $store->users->pluck('username')->implode(', ');

        // --- A. Server-Side Filter & Pagination Stok Barang Toko ---
        $stockSearch = $request->query('stock_search');
        $stocks = StoreStock::with('product.storeUnit')
            ->where('store_id', $store->id)
            ->when($stockSearch, function ($q) use ($stockSearch) {
                $q->whereHas('product', fn ($prod) => $prod->where('name', 'like', "%{$stockSearch}%"));
            })
            ->latest()
            ->paginate(10, ['*'], 'stock_page')
            ->withQueryString();

        // --- B. Server-Side Filter & Pagination Riwayat Sesi Opname Toko ---
        $opnameSearch = $request->query('opname_search');
        $opnameStatus = $request->query('opname_status');
        $opnameMonth = $request->query('opname_month');
        $opnameYear = $request->query('opname_year');

        $opnameHistory = StockAdjustment::with(['operator', 'items.product.storeUnit'])
            ->where('store_id', $store->id)
            ->when($opnameSearch, function ($q) use ($opnameSearch) {
                $q->where(function ($query) use ($opnameSearch) {
                    $query->where('code', 'like', "%{$opnameSearch}%")
                        ->orWhere('note', 'like', "%{$opnameSearch}%")
                        ->orWhereHas('operator', fn ($u) => $u->where('username', 'like', "%{$opnameSearch}%"));
                });
            })
            ->when($opnameStatus, function ($q) use ($opnameStatus) {
                if ($opnameStatus === 'started') {
                    $q->whereNull('completed_at')->whereNull('canceled_at');
                } elseif ($opnameStatus === 'completed') {
                    $q->whereNotNull('completed_at');
                } elseif ($opnameStatus === 'canceled') {
                    $q->whereNotNull('canceled_at');
                }
            })
            ->when($opnameMonth, fn ($q) => $q->whereMonth('created_at', $opnameMonth))
            ->when($opnameYear, fn ($q) => $q->whereYear('created_at', $opnameYear))
            ->latest()
            ->paginate(10, ['*'], 'opname_page')
            ->withQueryString();

        // Produk Katalog yang Belum Ada di Toko
        $allAssignedProductIds = StoreStock::where('store_id', $store->id)->pluck('product_id')->toArray();
        $availableProducts = Product::with('storeUnit')
            ->whereNotIn('id', $allAssignedProductIds)
            ->orderBy('name', 'asc')
            ->get();

        // Ambil Seluruh Stok Toko untuk Modal Opname Active/New
        $allStoreStocks = StoreStock::with('product.storeUnit')
            ->where('store_id', $store->id)
            ->get();

        // Opname Aktif / Draft
        $activeOpname = StockAdjustment::with('items')
            ->where('store_id', $store->id)
            ->whereNull('completed_at')
            ->whereNull('canceled_at')
            ->latest()
            ->first();

        return view('stores.manage', compact(
            'store',
            'cashierNames',
            'stocks',
            'opnameHistory',
            'availableProducts',
            'allStoreStocks',
            'activeOpname',
            'stockSearch',
            'opnameSearch',
            'opnameStatus',
            'opnameMonth',
            'opnameYear'
        ));
    }

    public function addProducts(Request $request, Store $store)
    {
        $request->validate([
            'product_ids' => ['required', 'array', 'min:1'],
            'product_ids.*' => ['exists:products,id'],
        ], [
            'product_ids.required' => 'Pilih minimal satu produk untuk ditambahkan ke toko.',
        ]);

        try {
            DB::beginTransaction();
            foreach ($request->product_ids as $productId) {
                StoreStock::firstOrCreate([
                    'store_id' => $store->id,
                    'product_id' => $productId,
                ], [
                    'stock' => 0,
                ]);
            }
            DB::commit();

            return redirect()->route('stores.manage', $store->id)->with('success', 'Produk berhasil ditambahkan ke toko.');
        } catch (Exception $e) {
            DB::rollBack();

            return redirect()->back()->with('error', 'Gagal menambahkan produk: '.$e->getMessage());
        }
    }

    public function saveOpnameDraft(Request $request, Store $store)
    {
        $request->validate([
            'adjustment_id' => ['nullable', 'exists:stock_adjustments,id'],
            'note' => ['nullable', 'string'],
            'actual_stocks' => ['required', 'array'],
            'actual_stocks.*' => ['nullable', 'numeric', 'min:0'],
            'item_notes' => ['nullable', 'array'],
        ]);

        try {
            DB::beginTransaction();

            if ($request->filled('adjustment_id')) {
                $adjustment = StockAdjustment::findOrFail($request->adjustment_id);
                $adjustment->update(['note' => $request->note]);
            } else {
                $adjustmentCode = 'ADJ-STR-'.date('Ymd').'-'.strtoupper(Str::random(4));
                $adjustment = StockAdjustment::create([
                    'code' => $adjustmentCode,
                    'store_id' => $store->id,
                    'warehouse_id' => null,
                    'operator_id' => auth()->id(),
                    'status' => 'pending',
                    'note' => $request->note,
                ]);
            }

            foreach ($request->actual_stocks as $productId => $actualStock) {
                if ($actualStock === null || $actualStock === '') {
                    continue;
                }

                $stockItem = StoreStock::where('store_id', $store->id)
                    ->where('product_id', $productId)
                    ->first();

                if ($stockItem) {
                    $systemStock = $stockItem->stock;
                    $actualStockVal = round((float) $actualStock, 1);
                    $adjustmentValue = round($actualStockVal - $systemStock, 1);
                    $itemNote = $request->item_notes[$productId] ?? null;

                    StockAdjustmentItem::updateOrCreate([
                        'stock_adjustment_id' => $adjustment->id,
                        'product_id' => $productId,
                    ], [
                        'system_stock' => $systemStock,
                        'actual_stock' => $actualStockVal,
                        'adjustment' => $adjustmentValue,
                        'note' => $itemNote,
                    ]);
                }
            }

            DB::commit();

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Progres opname toko berhasil disimpan sebagai draft.',
                    'adjustment_id' => $adjustment->id,
                ]);
            }

            return redirect()->route('stores.manage', $store->id)->with('success', 'Draft opname toko berhasil disimpan.');
        } catch (Exception $e) {
            DB::rollBack();
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
            }

            return redirect()->back()->with('error', 'Gagal menyimpan draft opname: '.$e->getMessage());
        }
    }

    public function completeOpname(Request $request, Store $store, $adjustmentId = null)
    {
        $request->validate([
            'note' => ['nullable', 'string'],
            'actual_stocks' => ['required', 'array'],
            'actual_stocks.*' => ['nullable', 'numeric', 'min:0'],
            'item_notes' => ['nullable', 'array'],
        ]);

        try {
            DB::beginTransaction();

            if ($adjustmentId && $adjustmentId != 0) {
                $adjustment = StockAdjustment::findOrFail($adjustmentId);
                $adjustment->update(['note' => $request->note]);
            } else {
                $adjustmentCode = 'ADJ-STR-'.date('Ymd').'-'.strtoupper(Str::random(4));
                $adjustment = StockAdjustment::create([
                    'code' => $adjustmentCode,
                    'store_id' => $store->id,
                    'warehouse_id' => null,
                    'operator_id' => auth()->id(),
                    'status' => 'pending',
                    'note' => $request->note,
                ]);
            }

            foreach ($request->actual_stocks as $productId => $actualStock) {
                if ($actualStock === null || $actualStock === '') {
                    continue;
                }

                $stockItem = StoreStock::where('store_id', $store->id)
                    ->where('product_id', $productId)
                    ->first();

                if ($stockItem) {
                    $systemStock = $stockItem->stock;
                    $actualStockVal = round((float) $actualStock, 1);
                    $adjustmentValue = round($actualStockVal - $systemStock, 1);
                    $itemNote = $request->item_notes[$productId] ?? null;

                    StockAdjustmentItem::updateOrCreate([
                        'stock_adjustment_id' => $adjustment->id,
                        'product_id' => $productId,
                    ], [
                        'system_stock' => $systemStock,
                        'actual_stock' => $actualStockVal,
                        'adjustment' => $adjustmentValue,
                        'note' => $itemNote,
                    ]);

                    $stockItem->update(['stock' => $actualStockVal]);
                }
            }

            $adjustment->update([
                'status' => 'completed',
                'completed_at' => now(),
            ]);

            DB::commit();

            return redirect()->route('stores.manage', $store->id)
                ->with('success', "Opname toko ({$adjustment->code}) resmi selesai & Stok Toko telah diperbarui!");
        } catch (Exception $e) {
            DB::rollBack();

            return redirect()->back()->with('error', 'Gagal menyelesaikan opname toko: '.$e->getMessage());
        }
    }

    public function cancelOpname(Store $store, StockAdjustment $adjustment)
    {
        try {
            if ($adjustment->completed_at) {
                return redirect()->back()->with('error', 'Opname yang sudah selesai tidak dapat dibatalkan.');
            }

            $adjustment->update([
                'status' => 'canceled',
                'canceled_at' => now(),
            ]);

            return redirect()->route('stores.manage', $store->id)->with('success', "Sesi Opname toko ({$adjustment->code}) dibatalkan.");
        } catch (Exception $e) {
            return redirect()->back()->with('error', 'Gagal membatalkan opname: '.$e->getMessage());
        }
    }

    public function getOpnameItems(StockAdjustment $adjustment)
    {
        $adjustment->load([
            'operator', 
            'items.product.warehouseUnit', 
            'items.product.storeUnit'
        ]);

        return response()->json($adjustment);
    }
}
