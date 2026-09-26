<?php

namespace App\Http\Controllers;

use App\Models\Discount;
use App\Models\DiscountItem;
use App\Models\Store;
use App\Models\StoreStock;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DiscountController extends Controller
{
    // 1. Index Riwayat Program Diskon
    public function index(Request $request)
    {
        $search = $request->query('search');
        $status = $request->query('status'); // active, expired, ended
        $storeId = $request->query('store_id');

        $now = now();

        $discounts = Discount::with(['store', 'items.product.storeUnit'])
            ->when($search, function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhereHas('store', fn ($s) => $s->where('name', 'like', "%{$search}%"));
            })
            ->when($storeId, fn ($q) => $q->where('store_id', $storeId))
            ->when($status, function ($q) use ($status, $now) {
                if ($status === 'active') {
                    $q->where('start_at', '<=', $now)->where('end_at', '>=', $now);
                } elseif ($status === 'expired') {
                    $q->where('end_at', '<', $now);
                } elseif ($status === 'upcoming') {
                    $q->where('start_at', '>', $now);
                }
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        $stores = Store::orderBy('name', 'asc')->get();

        return view('discounts.index', compact('discounts', 'stores', 'search', 'status', 'storeId'));
    }

    // 2. Halaman Terpisah Buat Program Diskon Baru
    public function create()
    {
        $stores = Store::orderBy('name', 'asc')->get();
        $defaultStoreId = old('store_id', $stores->first()->id ?? null);

        $storeStocks = collect();
        if ($defaultStoreId) {
            $storeStocks = StoreStock::with('product.storeUnit')
                ->where('store_id', $defaultStoreId)
                ->get();
        }

        return view('discounts.create', compact('stores', 'defaultStoreId', 'storeStocks'));
    }

    // AJAX API: Ambil Produk Terdaftar di StoreStock Toko
    public function getStoreProducts(Store $store)
    {
        $stocks = StoreStock::with('product.storeUnit')
            ->where('store_id', $store->id)
            ->get();

        return response()->json($stocks);
    }

    // 3. Simpan Program Diskon Baru
    public function store(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'store_id' => ['required', 'exists:stores,id'],
            'start_at' => ['required', 'date'],
            'end_at' => ['required', 'date', 'after:start_at'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.discount_percentage' => ['required', 'numeric', 'gt:0', 'lte:100'],
            'items.*.minimum_quantity' => ['required', 'numeric', 'gte:1'],
        ], [
            'name.required' => 'Nama program diskon wajib diisi.',
            'store_id.required' => 'Pilih cabang toko.',
            'end_at.after' => 'Waktu selesai diskon harus setelah waktu mulai.',
            'items.required' => 'Pilih minimal satu produk untuk didefinisikan diskonnya.',
        ]);

        try {
            DB::beginTransaction();

            $discount = Discount::create([
                'name' => $request->name,
                'store_id' => $request->store_id,
                'start_at' => $request->start_at,
                'end_at' => $request->end_at,
            ]);

            foreach ($request->items as $item) {
                DiscountItem::create([
                    'discount_id' => $discount->id,
                    'product_id' => $item['product_id'],
                    'discount_percentage' => round((float) $item['discount_percentage'], 2),
                    'minimum_quantity' => round((float) $item['minimum_quantity'], 1),
                ]);
            }

            DB::commit();

            return redirect()->route('discounts.index')
                ->with('success', "Program diskon '{$discount->name}' berhasil dibuat!");
        } catch (Exception $e) {
            DB::rollBack();

            return redirect()->back()->withInput()->with('error', 'Gagal membuat program diskon: '.$e->getMessage());
        }
    }

    // 4. Akhiri Diskon Secara Manual Lebih Cepat
    public function endNow(Discount $discount)
    {
        try {
            if (now()->greaterThanOrEqualTo($discount->end_at)) {
                return redirect()->back()->with('error', 'Program diskon ini memang sudah berakhir.');
            }

            // Set end_at ke waktu saat ini agar diskon otomatis berakhir
            $discount->update(['end_at' => now()]);

            return redirect()->route('discounts.index')
                ->with('success', "Program diskon '{$discount->name}' resmi diakhiri secara manual.");
        } catch (Exception $e) {
            return redirect()->back()->with('error', 'Gagal mengakhiri diskon: '.$e->getMessage());
        }
    }

    // AJAX API untuk Modal Detail Diskon
    public function getItems(Discount $discount)
    {
        $discount->load(['store', 'items.product.storeUnit']);

        return response()->json($discount);
    }
}
