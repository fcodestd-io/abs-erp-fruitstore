<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SupplierController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->query('search');

        $suppliers = Supplier::query()
            ->when($search, function ($query, $search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('address', 'like', "%{$search}%");
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('suppliers.index', compact('suppliers', 'search'));
    }

    public function store(Request $request)
    {
        // 1. Validasi Manual
        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'address' => ['nullable', 'string'],
        ], [
            'name.required' => 'Nama supplier wajib diisi.',
            'name.max' => 'Nama supplier maksimal 255 karakter.',
            'phone.max' => 'Nomor telepon maksimal 20 karakter.',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withInput()
                ->with('error', $validator->errors()->first());
        }

        // 2. Insert DB dengan Try-Catch
        try {
            Supplier::create($validator->validated());

            return redirect()->route('suppliers.index')
                ->with('success', 'Supplier berhasil ditambahkan.');
        } catch (Exception $e) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Gagal menyimpan data supplier: '.$e->getMessage());
        }
    }

    public function update(Request $request, Supplier $supplier)
    {
        // 1. Validasi Manual
        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'address' => ['nullable', 'string'],
        ], [
            'name.required' => 'Nama supplier wajib diisi.',
            'name.max' => 'Nama supplier maksimal 255 karakter.',
            'phone.max' => 'Nomor telepon maksimal 20 karakter.',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withInput()
                ->with('error', $validator->errors()->first());
        }

        // 2. Update DB dengan Try-Catch
        try {
            $supplier->update($validator->validated());

            return redirect()->route('suppliers.index')
                ->with('success', 'Data supplier berhasil diperbarui.');
        } catch (Exception $e) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Gagal memperbarui data supplier: '.$e->getMessage());
        }
    }

    public function destroy(Supplier $supplier)
    {
        try {
            // Cek relasi ke Purchase Order sebelum dihapus
            if (method_exists($supplier, 'purchaseOrders') && $supplier->purchaseOrders()->exists()) {
                return redirect()->route('suppliers.index')
                    ->with('error', 'Supplier tidak dapat dihapus karena memiliki riwayat Purchase Order.');
            }

            $supplier->delete();

            return redirect()->route('suppliers.index')
                ->with('success', 'Supplier berhasil dihapus.');
        } catch (Exception $e) {
            return redirect()->route('suppliers.index')
                ->with('error', 'Gagal menghapus supplier: '.$e->getMessage());
        }
    }
}
