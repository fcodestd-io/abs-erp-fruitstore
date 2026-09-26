<?php

namespace App\Http\Controllers;

use App\Models\Shipment;
use App\Models\ShipmentItem;
use App\Models\Store;
use App\Models\StoreStock;
use App\Models\Warehouse;
use App\Models\WarehouseStock;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ShipmentController extends Controller
{
    // 1. Halaman Index: Riwayat Shipment, Filter, Detail, & Konfirmasi Penerimaan Toko
    public function index(Request $request)
    {
        $search = $request->query('search');
        $status = $request->query('status');
        $month = $request->query('month');
        $year = $request->query('year');
        $storeId = $request->query('store_id');
        $warehouseId = $request->query('warehouse_id');

        $user = auth()->user();

        $query = Shipment::with([
            'warehouse',
            'store',
            'warehouseSupervisor',
            'cashier',
            'items.product.warehouseUnit',
            'items.product.storeUnit',
        ])
            ->when($user->role === 'warehouse_supervisor', function ($q) use ($user) {
                $q->where('warehouse_id', $user->warehouse_id);
            })
            ->when($user->role === 'cashier', function ($q) use ($user) {
                $q->where('store_id', $user->store_id);
            })
            ->when($search, function ($q) use ($search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('code', 'like', "%{$search}%")
                        ->orWhere('note', 'like', "%{$search}%")
                        ->orWhereHas('warehouse', fn ($w) => $w->where('name', 'like', "%{$search}%"))
                        ->orWhereHas('store', fn ($s) => $s->where('name', 'like', "%{$search}%"));
                });
            })
            ->when($status, fn ($q) => $q->where('status', $status))
            ->when($month, fn ($q) => $q->whereMonth('created_at', $month))
            ->when($year, fn ($q) => $q->whereYear('created_at', $year))
            ->when($storeId, fn ($q) => $q->where('store_id', $storeId))
            ->when($warehouseId, fn ($q) => $q->where('warehouse_id', $warehouseId));

        $shipments = $query->latest()->paginate(10)->withQueryString();

        $warehouses = Warehouse::orderBy('name', 'asc')->get();
        $stores = Store::orderBy('name', 'asc')->get();

        return view('shipments.index', compact(
            'shipments', 'warehouses', 'stores', 'search', 'status', 'month', 'year', 'storeId', 'warehouseId'
        ));
    }

    // 2. Halaman Terpisah Buat Surat Jalan / Shipment Baru
    public function create()
    {
        $user = auth()->user();
        $stores = Store::orderBy('name', 'asc')->get();

        if ($user->role === 'warehouse_supervisor') {
            $warehouses = Warehouse::where('id', $user->warehouse_id)->get();
            $defaultWarehouseId = $user->warehouse_id;
        } else {
            $warehouses = Warehouse::orderBy('name', 'asc')->get();
            $defaultWarehouseId = old('warehouse_id', $warehouses->first()->id ?? null);
        }

        // Ambil stok produk yang ada di gudang pengirim
        $warehouseStocks = collect();
        if ($defaultWarehouseId) {
            $warehouseStocks = WarehouseStock::with(['product.warehouseUnit', 'product.storeUnit'])
                ->where('warehouse_id', $defaultWarehouseId)
                ->where('stock', '>', 0)
                ->get();
        }

        return view('shipments.create', compact('warehouses', 'stores', 'defaultWarehouseId', 'warehouseStocks'));
    }

    // AJAX API: Mengambil stok produk gudang pengirim
    public function getWarehouseProducts(Warehouse $warehouse)
    {
        $stocks = WarehouseStock::with(['product.warehouseUnit', 'product.storeUnit'])
            ->where('warehouse_id', $warehouse->id)
            ->where('stock', '>', 0)
            ->get();

        return response()->json($stocks);
    }

    // 3. Simpan Pengiriman Barang (Tahap 1: Pending & Otomatis Potong Stok Gudang)
    // REVISI: cashier_id diset null saat pembuatan awal
    public function store(Request $request)
    {
        $request->validate([
            'warehouse_id' => ['required', 'exists:warehouses,id'],
            'store_id' => ['required', 'exists:stores,id'],
            'note' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.qty_sent' => ['required', 'numeric', 'gt:0'],
        ], [
            'warehouse_id.required' => 'Pilih gudang asal pengiriman.',
            'store_id.required' => 'Pilih toko cabang tujuan.',
            'items.required' => 'Pilih minimal satu produk untuk dikirim.',
        ]);

        try {
            DB::beginTransaction();

            $code = 'SHP-'.date('Ymd').'-'.strtoupper(Str::random(4));

            $shipment = Shipment::create([
                'code' => $code,
                'warehouse_id' => $request->warehouse_id,
                'store_id' => $request->store_id,
                'warehouse_supervisor_id' => auth()->id(),
                'cashier_id' => null, // Belum diisi saat pengiriman dibuat
                'status' => 'pending',
                'note' => $request->note,
            ]);

            foreach ($request->items as $item) {
                $qtySent = round((float) $item['qty_sent'], 1);

                // Validasi Stok Gudang Cukup
                $whStock = WarehouseStock::where('warehouse_id', $request->warehouse_id)
                    ->where('product_id', $item['product_id'])
                    ->first();

                if (! $whStock || $whStock->stock < $qtySent) {
                    throw new Exception('Stok produk di gudang tidak mencukupi untuk dikirim.');
                }

                // Potong Stok Gudang Pengirim
                $whStock->decrement('stock', $qtySent);

                ShipmentItem::create([
                    'shipment_id' => $shipment->id,
                    'product_id' => $item['product_id'],
                    'qty_sent' => $qtySent,
                    'qty_received' => 0, // Nilai awal 0 sebelum diterima toko
                ]);
            }

            DB::commit();

            return redirect()->route('shipments.index')
                ->with('success', "Surat Jalan Pengiriman ({$code}) berhasil dibuat & stok gudang telah berkurang.");
        } catch (Exception $e) {
            DB::rollBack();

            return redirect()->back()->withInput()->with('error', 'Gagal membuat pengiriman: '.$e->getMessage());
        }
    }

    // 4. Konfirmasi Penerimaan Toko (Tahap 2: Input Qty Received & Otomatis Tambah Stok Toko)
    // REVISI: cashier_id diisi dengan ID user yang memproses penerimaan barang (Kasir)
    public function complete(Request $request, Shipment $shipment)
    {
        if ($shipment->status !== 'pending') {
            return redirect()->back()->with('error', 'Pengiriman ini sudah selesai atau dibatalkan.');
        }

        $request->validate([
            'items' => ['required', 'array'],
            'items.*.qty_received' => ['required', 'numeric', 'min:0'],
        ]);

        try {
            DB::beginTransaction();

            foreach ($request->items as $itemId => $data) {
                $shipmentItem = ShipmentItem::where('shipment_id', $shipment->id)
                    ->where('id', $itemId)
                    ->first();

                if ($shipmentItem) {
                    $qtyReceived = round((float) $data['qty_received'], 1);

                    // Update Qty Diterima Toko
                    $shipmentItem->update([
                        'qty_received' => $qtyReceived,
                    ]);

                    // Otomatis Tambah / FirstOrCreate Stok Toko
                    $storeStock = StoreStock::where('store_id', $shipment->store_id)
                        ->where('product_id', $shipmentItem->product_id)
                        ->first();

                    if ($storeStock) {
                        $storeStock->increment('stock', $qtyReceived);
                    } else {
                        StoreStock::create([
                            'store_id' => $shipment->store_id,
                            'product_id' => $shipmentItem->product_id,
                            'stock' => $qtyReceived,
                        ]);
                    }
                }
            }

            // Update Header Shipment: Simpan Kasir Penerima & Set Completed
            $shipment->update([
                'status' => 'completed',
                'cashier_id' => auth()->id(), // Mencatat akun Kasir/Admin yang menerima barang
                'completed_at' => now(),
            ]);

            DB::commit();

            return redirect()->route('shipments.index')
                ->with('success', "Penerimaan pengiriman ({$shipment->code}) selesai & stok toko telah bertambah!");
        } catch (Exception $e) {
            DB::rollBack();

            return redirect()->back()->with('error', 'Gagal memproses penerimaan toko: '.$e->getMessage());
        }
    }

    // 5. Batalkan Pengiriman & Kembalikan Stok Gudang Asal
    public function cancel(Shipment $shipment)
    {
        if ($shipment->status !== 'pending') {
            return redirect()->back()->with('error', 'Pengiriman ini tidak dapat dibatalkan.');
        }

        try {
            DB::beginTransaction();

            // Kembalikan Stok Gudang Pengirim
            foreach ($shipment->items as $item) {
                WarehouseStock::where('warehouse_id', $shipment->warehouse_id)
                    ->where('product_id', $item->product_id)
                    ->increment('stock', $item->qty_sent);
            }

            $shipment->update([
                'status' => 'canceled',
                'canceled_at' => now(),
            ]);

            DB::commit();

            return redirect()->route('shipments.index')
                ->with('success', "Pengiriman ({$shipment->code}) dibatalkan & stok dikembalikan ke gudang.");
        } catch (Exception $e) {
            DB::rollBack();

            return redirect()->back()->with('error', 'Gagal membatalkan pengiriman: '.$e->getMessage());
        }
    }

    // AJAX API untuk Modal Detail Shipment
    public function getItems(Shipment $shipment)
    {
        $shipment->load([
            'warehouse',
            'store',
            'warehouseSupervisor',
            'cashier',
            'items.product.warehouseUnit',
            'items.product.storeUnit',
        ]);

        return response()->json($shipment);
    }
}
