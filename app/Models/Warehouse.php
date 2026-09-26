<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Warehouse extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'address',
    ];

    /**
     * Relasi ke Pengguna (Supervisor Gudang yang ditugaskan di gudang ini)
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * Relasi ke Stok Produk yang ada di Gudang ini
     */
    public function stocks(): HasMany
    {
        return $this->hasMany(WarehouseStock::class);
    }

    // /**
    //  * Relasi ke Purchase Order (PO) yang ditujukan ke gudang ini
    //  */
    // public function purchaseOrders(): HasMany
    // {
    //     return $this->hasMany(PurchaseOrder::class);
    // }

    // /**
    //  * Relasi ke Pengiriman (Shipment) yang dikirim dari gudang ini
    //  */
    // public function shipments(): HasMany
    // {
    //     return $this->hasMany(Shipment::class);
    // }
}
