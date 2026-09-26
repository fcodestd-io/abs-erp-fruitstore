<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Unit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class UnitController extends Controller
{
    public function index()
    {
        return response()->json(Unit::orderBy('name', 'asc')->get());
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:50|unique:units,name',
        ], [
            'name.required' => 'Nama satuan wajib diisi.',
            'name.unique' => 'Satuan ini sudah ada.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
            ], 422);
        }

        $unit = Unit::create(['name' => $request->name]);

        return response()->json([
            'success' => true,
            'message' => 'Satuan berhasil ditambahkan.',
            'unit' => $unit,
            'units' => Unit::orderBy('name', 'asc')->get(),
        ]);
    }

    public function destroy(Unit $unit)
    {
        // Cek jika satuan dipakai oleh produk
        $inUse = Product::where('store_unit_id', $unit->id)
            ->orWhere('warehouse_unit_id', $unit->id)
            ->exists();

        if ($inUse) {
            return response()->json([
                'success' => false,
                'message' => 'Satuan tidak dapat dihapus karena digunakan oleh produk!',
            ], 422);
        }

        $unit->delete();

        return response()->json([
            'success' => true,
            'message' => 'Satuan berhasil dihapus.',
            'units' => Unit::orderBy('name', 'asc')->get(),
        ]);
    }
}
