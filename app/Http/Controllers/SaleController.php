<?php

namespace App\Http\Controllers;

use App\Models\Discount;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Store;
use App\Models\StoreStock;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SaleController extends Controller
{
    // 1. Halaman Kasir / Form POS Penjualan
    public function create()
    {
        $user = auth()->user();

        if ($user->role === 'cashier') {
            $stores = Store::where('id', $user->store_id)->get();
            $defaultStoreId = $user->store_id;
        } else {
            $stores = Store::orderBy('name', 'asc')->get();
            $defaultStoreId = old('store_id', $stores->first()->id ?? null);
        }

        $storeStocks = collect();
        $activeDiscounts = collect();

        if ($defaultStoreId) {
            // Ambil produk yang tersedia di toko
            $storeStocks = StoreStock::with(['product.storeUnit'])
                ->where('store_id', $defaultStoreId)
                ->where('stock', '>', 0)
                ->get();

            // Ambil program diskon yang sedang AKTIF saat ini di toko tersebut
            $now = now();
            $activeDiscounts = Discount::with('items')
                ->where('store_id', $defaultStoreId)
                ->where('start_at', '<=', $now)
                ->where('end_at', '>=', $now)
                ->get()
                ->pluck('items')
                ->flatten();
        }

        return view('sales.create', compact('stores', 'defaultStoreId', 'storeStocks', 'activeDiscounts'));
    }

    // AJAX API: Mengambil Stok & Diskon Aktif berdasarkan Toko yang dipilih
    public function getStoreProducts(Store $store)
    {
        $now = now();

        $stocks = StoreStock::with(['product.storeUnit'])
            ->where('store_id', $store->id)
            ->where('stock', '>', 0)
            ->get();

        $discounts = Discount::with('items')
            ->where('store_id', $store->id)
            ->where('start_at', '<=', $now)
            ->where('end_at', '>=', $now)
            ->get()
            ->pluck('items')
            ->flatten();

        return response()->json([
            'stocks' => $stocks,
            'discounts' => $discounts,
        ]);
    }

    // 2. Simpan Transaksi Penjualan (Submit POS)
    public function store(Request $request)
    {
        $request->validate([
            'store_id' => ['required', 'exists:stores,id'],
            'payment_method' => ['required', 'in:cash,qris,transfer'],
            'paid_amount' => ['required', 'numeric', 'min:0'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.qty' => ['required', 'numeric', 'gt:0'],
            'items.*.price' => ['required', 'numeric', 'min:0'],
            'items.*.discount' => ['nullable', 'numeric', 'min:0'],
        ], [
            'items.required' => 'Keranjang belanja masih kosong.',
            'paid_amount.required' => 'Jumlah uang pembayaran wajib diisi.',
        ]);

        try {
            DB::beginTransaction();

            $code = 'INV-'.date('Ymd').'-'.strtoupper(Str::random(4));
            $subtotalSum = 0;
            $discountSum = 0;

            // Validasi & Hitung Item
            $itemsToCreate = [];
            foreach ($request->items as $item) {
                $qty = round((float) $item['qty'], 1);
                $price = (float) $item['price'];
                $itemDiscount = (float) ($item['discount'] ?? 0);

                // Cek Stok Toko
                $storeStock = StoreStock::where('store_id', $request->store_id)
                    ->where('product_id', $item['product_id'])
                    ->first();

                if (! $storeStock || $storeStock->stock < $qty) {
                    throw new Exception('Stok produk tidak mencukupi di toko.');
                }

                // Potong Stok Toko
                $storeStock->decrement('stock', $qty);

                $itemSubtotal = round(($qty * $price) - $itemDiscount, 2);
                $subtotalSum += round($qty * $price, 2);
                $discountSum += $itemDiscount;

                $itemsToCreate[] = [
                    'product_id' => $item['product_id'],
                    'qty' => $qty,
                    'price' => $price,
                    'discount' => $itemDiscount,
                    'subtotal' => $itemSubtotal,
                ];
            }

            $totalAmount = max(0, $subtotalSum - $discountSum);

            // Jika non-cash, otomatis set paidAmount sama dengan totalAmount
            if ($request->payment_method !== 'cash') {
                $paidAmount = $totalAmount;
                $changeAmount = 0;
            } else {
                $paidAmount = (float) $request->paid_amount;
                if ($paidAmount < $totalAmount) {
                    throw new Exception('Uang pembayaran cash kurang dari total tagihan.');
                }
                $changeAmount = max(0, $paidAmount - $totalAmount);
            }

            $changeAmount = ($request->payment_method === 'cash') ? max(0, $paidAmount - $totalAmount) : 0;

            // Simpan Sale Header
            $sale = Sale::create([
                'store_id' => $request->store_id,
                'code' => $code,
                'subtotal' => $subtotalSum,
                'discount' => $discountSum,
                'total_amount' => $totalAmount,
                'paid_amount' => $paidAmount,
                'change_amount' => $changeAmount,
                'payment_method' => $request->payment_method,
                'note' => $request->note,
                'cashier_id' => Auth::id(),
            ]);

            // Simpan Items
            foreach ($itemsToCreate as $itemData) {
                $itemData['sale_id'] = $sale->id;
                SaleItem::create($itemData);
            }

            DB::commit();

            // REDIRECT KEMBALI KE POS (sales.create) dengan Session Flash untuk Cetak Struk Otomatis
            return redirect()->route('sales.create')
                ->with('success', "Transaksi ({$code}) Berhasil!")
                ->with('print_sale_id', $sale->id);

        } catch (Exception $e) {
            DB::rollBack();

            return redirect()->back()->withInput()->with('error', 'Transaksi Gagal: '.$e->getMessage());
        }
    }

    // 3. Halaman Cetak Struk Belanja (Thermal POS Receipt)
    public function printReceipt(Sale $sale)
    {
        $sale->load(['store', 'items.product.storeUnit']);

        return view('sales.receipt', compact('sale'));
    }

    public function index(Request $request)
    {
        $search = $request->query('search');
        $storeId = $request->query('store_id');
        $startDate = $request->query('start_date', date('Y-m-01'));
        $endDate = $request->query('end_date', date('Y-m-d'));

        $user = auth()->user();

        $query = Sale::with([
            'store',
            'cashier',
            'items.product.storeUnit',
        ])
            ->when(
                $user->role === 'cashier',
                fn ($q) => $q->where('store_id', $user->store_id)
            )
            ->when(
                $storeId,
                fn ($q) => $q->where('store_id', $storeId)
            )
            ->when(
                $search,
                fn ($q) => $q->where('code', 'like', "%{$search}%")
            )
            ->when(
                $startDate,
                fn ($q) => $q->whereDate('created_at', '>=', $startDate)
            )
            ->when(
                $endDate,
                fn ($q) => $q->whereDate('created_at', '<=', $endDate)
            );

        // Hitung ringkasan statistik (KPI)
        $totalOmset = (clone $query)->sum('total_amount');
        $totalDiscount = (clone $query)->sum('discount');
        $totalCount = (clone $query)->count();

        $avgTransaction = $totalCount > 0
            ? ($totalOmset / $totalCount)
            : 0;

        // Breakdown metode pembayaran
        $cashOmset = (clone $query)
            ->where('payment_method', 'cash')
            ->sum('total_amount');

        $qrisOmset = (clone $query)
            ->where('payment_method', 'qris')
            ->sum('total_amount');

        $transferOmset = (clone $query)
            ->where('payment_method', 'transfer')
            ->sum('total_amount');

        $sales = $query
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $stores = Store::orderBy('name', 'asc')->get();

        return view('sales.index', compact(
            'sales',
            'stores',
            'search',
            'storeId',
            'startDate',
            'endDate',
            'totalOmset',
            'totalDiscount',
            'totalCount',
            'avgTransaction',
            'cashOmset',
            'qrisOmset',
            'transferOmset'
        ));
    }

    // Endpoint API pendukung untuk Modal Detail Sales Items
    public function getItems(Sale $sale)
    {
        $sale->load(['store', 'items.product.storeUnit']);

        return response()->json($sale);
    }
}
