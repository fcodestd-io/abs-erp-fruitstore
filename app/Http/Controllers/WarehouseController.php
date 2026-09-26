<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\StockAdjustment;
use App\Models\StockAdjustmentItem;
use App\Models\Warehouse;
use App\Models\WarehouseStock;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class WarehouseController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->query('search');

        $warehouses = Warehouse::with(['users' => function ($q) {
            $q->where('role', 'warehouse_supervisor');
        }])
            ->when($search, fn ($q) => $q->where('name', 'like', "%{$search}%")->orWhere('address', 'like', "%{$search}%"))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('warehouses.index', compact('warehouses', 'search'));
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:255'],
            'address' => ['required', 'string'],
        ], [
            'name.required' => 'Nama gudang wajib diisi.',
            'address.required' => 'Alamat gudang wajib diisi.',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withInput()->with('error', $validator->errors()->first());
        }

        try {
            Warehouse::create($validator->validated());

            return redirect()->route('warehouses.index')->with('success', 'Gudang berhasil ditambahkan.');
        } catch (Exception $e) {
            return redirect()->back()->withInput()->with('error', 'Gagal menambahkan gudang: '.$e->getMessage());
        }
    }

    public function update(Request $request, Warehouse $warehouse)
    {
        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:255'],
            'address' => ['required', 'string'],
        ], [
            'name.required' => 'Nama gudang wajib diisi.',
            'address.required' => 'Alamat gudang wajib diisi.',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withInput()->with('error', $validator->errors()->first());
        }

        try {
            $warehouse->update($validator->validated());

            return redirect()->route('warehouses.index')->with('success', 'Data gudang berhasil diperbarui.');
        } catch (Exception $e) {
            return redirect()->back()->withInput()->with('error', 'Gagal memperbarui gudang: '.$e->getMessage());
        }
    }

    public function destroy(Warehouse $warehouse)
    {
        try {
            $warehouse->delete();

            return redirect()->route('warehouses.index')->with('success', 'Gudang berhasil dihapus.');
        } catch (Exception $e) {
            return redirect()->route('warehouses.index')->with('error', 'Gagal menghapus gudang: '.$e->getMessage());
        }
    }

    // 2. Halaman Kelola Gudang
    public function manage(Request $request, Warehouse $warehouse)
    {
        $warehouse->load(['users' => fn ($q) => $q->where('role', 'warehouse_supervisor')]);
        $supervisorNames = $warehouse->users->pluck('username')->implode(', ');

        // --- A. Server-Side Filter & Pagination Stok Barang Gudang ---
        $stockSearch = $request->query('stock_search');
        $stocks = WarehouseStock::with('product.warehouseUnit')
            ->where('warehouse_id', $warehouse->id)
            ->when($stockSearch, function ($q) use ($stockSearch) {
                $q->whereHas('product', fn ($prod) => $prod->where('name', 'like', "%{$stockSearch}%"));
            })
            ->latest()
            ->paginate(10, ['*'], 'stock_page')
            ->withQueryString();

        // --- B. Server-Side Filter & Pagination Riwayat Sesi Opname ---
        $opnameSearch = $request->query('opname_search');
        $opnameStatus = $request->query('opname_status');
        $opnameMonth = $request->query('opname_month');
        $opnameYear = $request->query('opname_year');

        $opnameHistory = StockAdjustment::with(['operator', 'items.product.warehouseUnit'])
            ->where('warehouse_id', $warehouse->id)
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

        // Produk Katalog yang Belum Ada di Gudang
        $allAssignedProductIds = WarehouseStock::where('warehouse_id', $warehouse->id)->pluck('product_id')->toArray();
        $availableProducts = Product::with('warehouseUnit')
            ->whereNotIn('id', $allAssignedProductIds)
            ->orderBy('name', 'asc')
            ->get();

        // Ambil Seluruh Stok Gudang untuk Modal Opname Active/New
        $allWarehouseStocks = WarehouseStock::with('product.warehouseUnit')
            ->where('warehouse_id', $warehouse->id)
            ->get();

        // Opname Aktif / Draft
        $activeOpname = StockAdjustment::with('items')
            ->where('warehouse_id', $warehouse->id)
            ->whereNull('completed_at')
            ->whereNull('canceled_at')
            ->latest()
            ->first();

        return view('warehouses.manage', compact(
            'warehouse',
            'supervisorNames',
            'stocks',
            'opnameHistory',
            'availableProducts',
            'allWarehouseStocks',
            'activeOpname',
            'stockSearch',
            'opnameSearch',
            'opnameStatus',
            'opnameMonth',
            'opnameYear'
        ));
    }

    public function addProducts(Request $request, Warehouse $warehouse)
    {
        $request->validate([
            'product_ids' => ['required', 'array', 'min:1'],
            'product_ids.*' => ['exists:products,id'],
        ], [
            'product_ids.required' => 'Pilih minimal satu produk untuk ditambahkan ke gudang.',
        ]);

        try {
            DB::beginTransaction();
            foreach ($request->product_ids as $productId) {
                WarehouseStock::firstOrCreate([
                    'warehouse_id' => $warehouse->id,
                    'product_id' => $productId,
                ], [
                    'stock' => 0,
                ]);
            }
            DB::commit();

            return redirect()->route('warehouses.manage', $warehouse->id)->with('success', 'Produk berhasil ditambahkan ke gudang.');
        } catch (Exception $e) {
            DB::rollBack();

            return redirect()->back()->with('error', 'Gagal menambahkan produk: '.$e->getMessage());
        }
    }

    public function saveOpnameDraft(Request $request, Warehouse $warehouse)
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
                $adjustmentCode = 'ADJ-WH-'.date('Ymd').'-'.strtoupper(Str::random(4));
                $adjustment = StockAdjustment::create([
                    'code' => $adjustmentCode,
                    'warehouse_id' => $warehouse->id,
                    'store_id' => null,
                    'operator_id' => auth()->id(),
                    'status' => 'pending',
                    'note' => $request->note,
                ]);
            }

            foreach ($request->actual_stocks as $productId => $actualStock) {
                if ($actualStock === null || $actualStock === '') {
                    continue;
                }

                $stockItem = WarehouseStock::where('warehouse_id', $warehouse->id)
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
                    'message' => 'Progres opname berhasil disimpan sebagai draft.',
                    'adjustment_id' => $adjustment->id,
                ]);
            }

            return redirect()->route('warehouses.manage', $warehouse->id)->with('success', 'Draft opname berhasil disimpan.');
        } catch (Exception $e) {
            DB::rollBack();
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
            }

            return redirect()->back()->with('error', 'Gagal menyimpan draft opname: '.$e->getMessage());
        }
    }

    public function completeOpname(Request $request, Warehouse $warehouse, $adjustmentId = null)
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
                $adjustmentCode = 'ADJ-WH-'.date('Ymd').'-'.strtoupper(Str::random(4));
                $adjustment = StockAdjustment::create([
                    'code' => $adjustmentCode,
                    'warehouse_id' => $warehouse->id,
                    'store_id' => null,
                    'operator_id' => auth()->id(),
                    'status' => 'pending',
                    'note' => $request->note,
                ]);
            }

            foreach ($request->actual_stocks as $productId => $actualStock) {
                if ($actualStock === null || $actualStock === '') {
                    continue;
                }

                $stockItem = WarehouseStock::where('warehouse_id', $warehouse->id)
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

            return redirect()->route('warehouses.manage', $warehouse->id)
                ->with('success', "Opname ({$adjustment->code}) resmi selesai & Stok Gudang telah diperbarui!");
        } catch (Exception $e) {
            DB::rollBack();

            return redirect()->back()->with('error', 'Gagal menyelesaikan opname: '.$e->getMessage());
        }
    }

    public function cancelOpname(Warehouse $warehouse, StockAdjustment $adjustment)
    {
        try {
            if ($adjustment->completed_at) {
                return redirect()->back()->with('error', 'Opname yang sudah selesai tidak dapat dibatalkan.');
            }

            $adjustment->update([
                'status' => 'canceled',
                'canceled_at' => now(),
            ]);

            return redirect()->route('warehouses.manage', $warehouse->id)->with('success', "Sesi Opname ({$adjustment->code}) dibatalkan.");
        } catch (Exception $e) {
            return redirect()->back()->with('error', 'Gagal membatalkan opname: '.$e->getMessage());
        }
    }

    // AJAX API untuk Modal Detail Items Opname
    public function getOpnameItems(StockAdjustment $adjustment)
    {
        $adjustment->load([
            'operator',
            'items.product.warehouseUnit',
            'items.product.storeUnit',
        ]);

        return response()->json($adjustment);
    }
}
