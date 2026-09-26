<?php

namespace App\Http\Controllers;

use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\Supplier;
use App\Models\Warehouse;
use App\Models\WarehouseStock;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PurchaseOrderController extends Controller
{
    // 1. Halaman Index: Riwayat PO, Filter Complete/Canceled/Pending, Detail & Terima Barang
    public function index(Request $request)
    {
        $search = $request->query('search');
        $status = $request->query('status');
        $month = $request->query('month');
        $year = $request->query('year');
        $supplierId = $request->query('supplier_id');

        $user = auth()->user();

        $query = PurchaseOrder::with(['supplier', 'warehouse', 'operator', 'items.product.warehouseUnit'])
            ->when($user->role === 'warehouse_supervisor', function ($q) use ($user) {
                $q->where('warehouse_id', $user->warehouse_id);
            })
            ->when($search, function ($q) use ($search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('po_code', 'like', "%{$search}%")
                        ->orWhere('note', 'like', "%{$search}%")
                        ->orWhereHas('supplier', fn ($s) => $s->where('name', 'like', "%{$search}%"));
                });
            })
            ->when($status, fn ($q) => $q->where('status', $status))
            ->when($month, fn ($q) => $q->whereMonth('created_at', $month))
            ->when($year, fn ($q) => $q->whereYear('created_at', $year))
            ->when($supplierId, fn ($q) => $q->where('supplier_id', $supplierId));

        $purchaseOrders = $query->latest()->paginate(10)->withQueryString();
        $suppliers = Supplier::orderBy('name', 'asc')->get();

        return view('purchase_orders.index', compact(
            'purchaseOrders', 'suppliers', 'search', 'status', 'month', 'year', 'supplierId'
        ));
    }

    // 2. Halaman Buat PO Baru (Halaman Terpisah)
    public function create()
    {
        $user = auth()->user();
        $suppliers = Supplier::orderBy('name', 'asc')->get();

        if ($user->role === 'warehouse_supervisor') {
            $warehouses = Warehouse::where('id', $user->warehouse_id)->get();
            $defaultWarehouseId = $user->warehouse_id;
        } else {
            $warehouses = Warehouse::orderBy('name', 'asc')->get();
            $defaultWarehouseId = old('warehouse_id', $warehouses->first()->id ?? null);
        }

        // Ambil produk HANYA yang terdaftar di WarehouseStock gudang ini
        $warehouseStocks = collect();
        if ($defaultWarehouseId) {
            $warehouseStocks = WarehouseStock::with('product.warehouseUnit')
                ->where('warehouse_id', $defaultWarehouseId)
                ->get();
        }

        return view('purchase_orders.create', compact('suppliers', 'warehouses', 'defaultWarehouseId', 'warehouseStocks'));
    }

    // AJAX API: Mengambil produk terdaftar di Gudang tertentu untuk pilihan PO
    public function getProductsByWarehouse(Warehouse $warehouse)
    {
        $stocks = WarehouseStock::with('product.warehouseUnit')
            ->where('warehouse_id', $warehouse->id)
            ->get();

        return response()->json($stocks);
    }

    // 3. Simpan PO Baru (Tahap 1: Pending Order)
    public function store(Request $request)
    {
        $request->validate([
            'supplier_id' => ['nullable', 'exists:suppliers,id'],
            'warehouse_id' => ['required', 'exists:warehouses,id'],
            'note' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.qty_ordered' => ['required', 'numeric', 'gt:0'],
            'items.*.price' => ['required', 'numeric', 'min:0'],
        ], [
            'warehouse_id.required' => 'Pilih gudang tujuan.',
            'items.required' => 'Pilih minimal satu produk untuk diorder.',
        ]);

        try {
            DB::beginTransaction();

            $poCode = 'PO-'.date('Ymd').'-'.strtoupper(Str::random(4));
            $totalAmount = 0;

            $po = PurchaseOrder::create([
                'po_code' => $poCode,
                'supplier_id' => $request->supplier_id,
                'warehouse_id' => $request->warehouse_id,
                'operator_id' => auth()->id(),
                'status' => 'pending',
                'note' => $request->note,
                'total_amount' => 0,
            ]);

            foreach ($request->items as $item) {
                $qtyOrdered = round((float) $item['qty_ordered'], 1);
                $price = (float) $item['price'];
                $subtotal = round($qtyOrdered * $price, 2);
                $totalAmount += $subtotal;

                PurchaseOrderItem::create([
                    'purchase_order_id' => $po->id,
                    'product_id' => $item['product_id'],
                    'qty_ordered' => $qtyOrdered,
                    'qty_actual' => 0, // Nullable / 0 saat pembuatan awal
                    'price' => $price,
                    'subtotal' => $subtotal,
                ]);
            }

            $po->update(['total_amount' => $totalAmount]);

            DB::commit();

            return redirect()->route('purchase-orders.index')
                ->with('success', "Purchase Order ({$poCode}) berhasil dibuat!");
        } catch (Exception $e) {
            DB::rollBack();

            return redirect()->back()->withInput()->with('error', 'Gagal membuat PO: '.$e->getMessage());
        }
    }

    // 4. Selesaikan PO / Penerimaan Barang (Tahap 2: Input Qty Actual & Update Stok Gudang)
    public function complete(Request $request, PurchaseOrder $purchaseOrder)
    {
        if ($purchaseOrder->status !== 'pending') {
            return redirect()->back()->with('error', 'PO ini sudah selesai atau dibatalkan.');
        }

        $request->validate([
            'items' => ['required', 'array'],
            'items.*.qty_actual' => ['required', 'numeric', 'min:0'],
            'items.*.price' => ['required', 'numeric', 'min:0'],
        ]);

        try {
            DB::beginTransaction();

            $newTotalAmount = 0;

            foreach ($request->items as $itemId => $data) {
                $poItem = PurchaseOrderItem::where('purchase_order_id', $purchaseOrder->id)
                    ->where('id', $itemId)
                    ->first();

                if ($poItem) {
                    $qtyActual = round((float) $data['qty_actual'], 1);
                    $price = (float) $data['price'];
                    $subtotal = round($qtyActual * $price, 2);
                    $newTotalAmount += $subtotal;

                    // Update detail item
                    $poItem->update([
                        'qty_actual' => $qtyActual,
                        'price' => $price,
                        'subtotal' => $subtotal,
                    ]);

                    // OTOMATIS UPDATE STOK UTAMA GUDANG
                    $warehouseStock = WarehouseStock::where('warehouse_id', $purchaseOrder->warehouse_id)
                        ->where('product_id', $poItem->product_id)
                        ->first();

                    if ($warehouseStock) {
                        $warehouseStock->increment('stock', $qtyActual);
                    } else {
                        WarehouseStock::create([
                            'warehouse_id' => $purchaseOrder->warehouse_id,
                            'product_id' => $poItem->product_id,
                            'stock' => $qtyActual,
                        ]);
                    }
                }
            }

            // Update Header PO
            $purchaseOrder->update([
                'status' => 'completed',
                'total_amount' => $newTotalAmount,
                'completed_at' => now(),
            ]);

            DB::commit();

            return redirect()->route('purchase-orders.index')
                ->with('success', "PO ({$purchaseOrder->po_code}) resmi diselesaikan & stok gudang telah bertambah!");
        } catch (Exception $e) {
            DB::rollBack();

            return redirect()->back()->with('error', 'Gagal memproses penerimaan PO: '.$e->getMessage());
        }
    }

    // 5. Batalkan PO
    public function cancel(PurchaseOrder $purchaseOrder)
    {
        if ($purchaseOrder->status !== 'pending') {
            return redirect()->back()->with('error', 'PO ini tidak dapat dibatalkan.');
        }

        try {
            $purchaseOrder->update([
                'status' => 'canceled',
                'canceled_at' => now(),
            ]);

            return redirect()->route('purchase-orders.index')
                ->with('success', "PO ({$purchaseOrder->po_code}) berhasil dibatalkan.");
        } catch (Exception $e) {
            return redirect()->back()->with('error', 'Gagal membatalkan PO: '.$e->getMessage());
        }
    }

    // AJAX API untuk Modal Detail PO
    public function getItems(PurchaseOrder $purchaseOrder)
    {
        $purchaseOrder->load(['supplier', 'warehouse', 'operator', 'items.product.warehouseUnit']);

        return response()->json($purchaseOrder);
    }
}
