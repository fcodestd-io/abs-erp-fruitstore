<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseOrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'purchase_order_id',
        'product_id',
        'qty_ordered',
        'qty_actual',
        'price',
        'subtotal',
    ];

    protected $casts = [
        'qty_ordered' => 'decimal:3',
        'qty_actual' => 'decimal:3',
        'price' => 'decimal:2',
        'subtotal' => 'decimal:2',
    ];

    /**
     * Relasi kembali ke Header Purchase Order
     */
    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class, 'purchase_order_id');
    }

    /**
     * Relasi ke Produk
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
