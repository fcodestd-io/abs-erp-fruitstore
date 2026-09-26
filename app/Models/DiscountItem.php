<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DiscountItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'discount_id',
        'product_id',
        'discount_percentage',
        'minimum_quantity',
    ];

    protected $casts = [
        'discount_percentage' => 'decimal:2',
        'minimum_quantity' => 'decimal:3',
    ];

    /**
     * Relasi kembali ke Header Diskon
     */
    public function discount(): BelongsTo
    {
        return $this->belongsTo(Discount::class, 'discount_id');
    }

    /**
     * Relasi ke Produk
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
