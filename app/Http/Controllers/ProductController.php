<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Unit;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->query('search');

        $products = Product::with(['storeUnit', 'warehouseUnit'])
            ->when($search, function ($query, $search) {
                $query->where('name', 'like', "%{$search}%");
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        $units = Unit::orderBy('name', 'asc')->get();

        return view('products.index', compact('products', 'units', 'search'));
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:255'],
            'cost_price' => ['required', 'numeric', 'min:0'],
            'selling_price' => ['required', 'numeric', 'min:0'],
            'store_unit_id' => ['required', 'exists:units,id'],
            'warehouse_unit_id' => ['required', 'exists:units,id'],
        ], [
            'name.required' => 'Nama produk wajib diisi.',
            'cost_price.required' => 'Harga modal wajib diisi.',
            'selling_price.required' => 'Harga jual wajib diisi.',
            'store_unit_id.required' => 'Satuan toko wajib dipilih.',
            'warehouse_unit_id.required' => 'Satuan gudang wajib dipilih.',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withInput()
                ->with('error', $validator->errors()->first());
        }

        try {
            Product::create($validator->validated());

            return redirect()->route('products.index')
                ->with('success', 'Produk berhasil ditambahkan.');
        } catch (Exception $e) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Gagal menyimpan produk: '.$e->getMessage());
        }
    }

    public function update(Request $request, Product $product)
    {
        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:255'],
            'cost_price' => ['required', 'numeric', 'min:0'],
            'selling_price' => ['required', 'numeric', 'min:0'],
            'store_unit_id' => ['required', 'exists:units,id'],
            'warehouse_unit_id' => ['required', 'exists:units,id'],
        ], [
            'name.required' => 'Nama produk wajib diisi.',
            'cost_price.required' => 'Harga modal wajib diisi.',
            'selling_price.required' => 'Harga jual wajib diisi.',
            'store_unit_id.required' => 'Satuan toko wajib dipilih.',
            'warehouse_unit_id.required' => 'Satuan gudang wajib dipilih.',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withInput()
                ->with('error', $validator->errors()->first());
        }

        try {
            $product->update($validator->validated());

            return redirect()->route('products.index')
                ->with('success', 'Data produk berhasil diperbarui.');
        } catch (Exception $e) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Gagal memperbarui produk: '.$e->getMessage());
        }
    }

    public function destroy(Product $product)
    {
        try {
            $product->delete();

            return redirect()->route('products.index')
                ->with('success', 'Produk berhasil dihapus.');
        } catch (Exception $e) {
            return redirect()->route('products.index')
                ->with('error', 'Gagal menghapus produk: '.$e->getMessage());
        }
    }
}
