<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockAdjustmentItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'stock_adjustment_id',
        'product_id',
        'system_stock',
        'actual_stock',
        'adjustment',
        'note',
    ];

    /**
     * Relasi kembali ke Header Opname
     */
    public function stockAdjustment(): BelongsTo
    {
        return $this->belongsTo(StockAdjustment::class, 'stock_adjustment_id');
    }

    /**
     * Relasi ke Produk
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
