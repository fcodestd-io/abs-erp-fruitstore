<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StockAdjustment extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'warehouse_id',
        'store_id',
        'operator_id',
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
     * Relasi ke Gudang (jika opname dilakukan di gudang)
     */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    /**
     * Relasi ke Toko (jika opname dilakukan di toko)
     */
    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    /**
     * Relasi ke Operator / Staf yang melakukan opname
     */
    public function operator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'operator_id');
    }

    /**
     * Relasi ke Rincian Item Opname
     */
    public function items(): HasMany
    {
        return $this->hasMany(StockAdjustmentItem::class, 'stock_adjustment_id');
    }
}
