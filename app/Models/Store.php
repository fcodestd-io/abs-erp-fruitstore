<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Store extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'address',
    ];

    /**
     * Relasi ke Pengguna (Kasir / Staf yang ditugaskan di toko ini)
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    // /**
    //  * Relasi ke Stok Produk yang ada di Toko ini
    //  */
    // public function stocks(): HasMany
    // {
    //     return $this->hasMany(StoreStock::class);
    // }

    // /**
    //  * Relasi ke Pengiriman (Shipment) yang diterima oleh toko ini
    //  */
    // public function shipments(): HasMany
    // {
    //     return $this->hasMany(Shipment::class);
    // }

    // /**
    //  * Relasi ke Transaksi Penjualan Kasir (Sales / POS)
    //  */
    // public function sales(): HasMany
    // {
    //     return $this->hasMany(Sale::class);
    // }
}