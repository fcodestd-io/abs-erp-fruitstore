<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Warehouse;
use App\Models\Store;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Exception;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->query('search');

        $users = User::with(['warehouse', 'store'])
            ->when($search, function ($query, $search) {
                $query->where('username', 'like', "%{$search}%")
                    ->orWhere('role', 'like', "%{$search}%");
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        $warehouses = Warehouse::select('id', 'name')->get();
        $stores = Store::select('id', 'name')->get();

        return view('users.index', compact('users', 'warehouses', 'stores', 'search'));
    }

    public function store(Request $request)
    {
        // Owner tidak boleh dibuat dari form
        $validator = Validator::make($request->all(), [
            'username' => ['required', 'string', 'max:255', 'unique:users,username'],
            'password' => ['required', 'string', 'min:6'],
            'role' => ['required', 'in:admin,warehouse_supervisor,cashier'],
            'warehouse_id' => ['required_if:role,warehouse_supervisor', 'nullable', 'exists:warehouses,id'],
            'store_id' => ['required_if:role,cashier', 'nullable', 'exists:stores,id'],
        ], [
            'username.required' => 'Username wajib diisi.',
            'username.unique' => 'Username sudah digunakan.',
            'password.required' => 'Password wajib diisi.',
            'password.min' => 'Password minimal 6 karakter.',
            'role.required' => 'Role wajib dipilih.',
            'role.in' => 'Role tidak valid.',
            'warehouse_id.required_if' => 'Gudang wajib dipilih untuk Supervisor Gudang.',
            'store_id.required_if' => 'Toko wajib dipilih untuk Kasir.',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withInput()
                ->with('error', $validator->errors()->first());
        }

        try {
            $data = $validator->validated();
            $data['password'] = Hash::make($data['password']);

            // Reset relasi sesuai role
            if ($data['role'] === 'admin') {
                $data['warehouse_id'] = null;
                $data['store_id'] = null;
            } elseif ($data['role'] === 'warehouse_supervisor') {
                $data['store_id'] = null;
            } elseif ($data['role'] === 'cashier') {
                $data['warehouse_id'] = null;
            }

            User::create($data);

            return redirect()->route('users.index')
                ->with('success', 'Pengguna berhasil ditambahkan.');
        } catch (Exception $e) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Gagal menambahkan pengguna: ' . $e->getMessage());
        }
    }

    public function update(Request $request, User $user)
    {
        // Jika user yang diedit adalah Owner
        if ($user->role === 'owner') {
            $validator = Validator::make($request->all(), [
                'username' => ['required', 'string', 'max:255', 'unique:users,username,' . $user->id],
                'password' => ['nullable', 'string', 'min:6'],
            ], [
                'username.required' => 'Username wajib diisi.',
                'username.unique' => 'Username sudah digunakan.',
                'password.min' => 'Password minimal 6 karakter.',
            ]);

            if ($validator->fails()) {
                return redirect()->back()
                    ->withInput()
                    ->with('error', $validator->errors()->first());
            }

            try {
                $data = ['username' => $request->username];
                if ($request->filled('password')) {
                    $data['password'] = Hash::make($request->password);
                }

                $user->update($data);

                return redirect()->route('users.index')
                    ->with('success', 'Data Owner berhasil diperbarui.');
            } catch (Exception $e) {
                return redirect()->back()
                    ->withInput()
                    ->with('error', 'Gagal memperbarui data Owner: ' . $e->getMessage());
            }
        }

        // Untuk User Non-Owner
        $validator = Validator::make($request->all(), [
            'username' => ['required', 'string', 'max:255', 'unique:users,username,' . $user->id],
            'password' => ['nullable', 'string', 'min:6'],
            'role' => ['required', 'in:admin,warehouse_supervisor,cashier'],
            'warehouse_id' => ['required_if:role,warehouse_supervisor', 'nullable', 'exists:warehouses,id'],
            'store_id' => ['required_if:role,cashier', 'nullable', 'exists:stores,id'],
        ], [
            'username.required' => 'Username wajib diisi.',
            'username.unique' => 'Username sudah digunakan.',
            'password.min' => 'Password minimal 6 karakter.',
            'role.required' => 'Role wajib dipilih.',
            'role.in' => 'Role tidak valid.',
            'warehouse_id.required_if' => 'Gudang wajib dipilih untuk Supervisor Gudang.',
            'store_id.required_if' => 'Toko wajib dipilih untuk Kasir.',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withInput()
                ->with('error', $validator->errors()->first());
        }

        try {
            $data = $validator->validated();
            
            if ($request->filled('password')) {
                $data['password'] = Hash::make($request->password);
            } else {
                unset($data['password']);
            }

            // Reset relasi sesuai role
            if ($data['role'] === 'admin') {
                $data['warehouse_id'] = null;
                $data['store_id'] = null;
            } elseif ($data['role'] === 'warehouse_supervisor') {
                $data['store_id'] = null;
            } elseif ($data['role'] === 'cashier') {
                $data['warehouse_id'] = null;
            }

            $user->update($data);

            return redirect()->route('users.index')
                ->with('success', 'Data pengguna berhasil diperbarui.');
        } catch (Exception $e) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Gagal memperbarui data pengguna: ' . $e->getMessage());
        }
    }

    public function destroy(User $user)
    {
        // Proteksi mutlak agar Owner tidak bisa dihapus
        if ($user->role === 'owner') {
            return redirect()->route('users.index')
                ->with('error', 'Akun Owner utama tidak dapat dihapus!');
        }

        try {
            $user->delete();

            return redirect()->route('users.index')
                ->with('success', 'Pengguna berhasil dihapus.');
        } catch (Exception $e) {
            return redirect()->route('users.index')
                ->with('error', 'Gagal menghapus pengguna: ' . $e->getMessage());
        }
    }
}