<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShipmentItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'shipment_id',
        'product_id',
        'qty_sent',
        'qty_received',
    ];

    protected $casts = [
        'qty_sent' => 'decimal:3',
        'qty_received' => 'decimal:3',
    ];

    /**
     * Relasi kembali ke Header Shipment
     */
    public function shipment(): BelongsTo
    {
        return $this->belongsTo(Shipment::class, 'shipment_id');
    }

    /**
     * Relasi ke Produk
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
