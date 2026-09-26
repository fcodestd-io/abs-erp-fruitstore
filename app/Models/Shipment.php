<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Shipment extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'warehouse_id',
        'store_id',
        'warehouse_supervisor_id',
        'cashier_id',
        'status',
        'note',
        'canceled_at',
        'completed_at',
    ];

    protected $casts = [
        'canceled_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    /**
     * Relasi ke Gudang Asal Pengiriman
     */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    /**
     * Relasi ke Toko Tujuan Pengiriman
     */
    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    /**
     * Relasi ke Supervisor Gudang yang Mengirim Barang
     */
    public function warehouseSupervisor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'warehouse_supervisor_id');
    }

    /**
     * Relasi ke Kasir Toko yang Menerima Barang
     */
    public function cashier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cashier_id');
    }

    /**
     * Relasi ke Item Barang Pengiriman
     */
    public function items(): HasMany
    {
        return $this->hasMany(ShipmentItem::class, 'shipment_id');
    }
}
