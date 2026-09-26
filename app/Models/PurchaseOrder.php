<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PurchaseOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'po_code',
        'supplier_id',
        'warehouse_id',
        'operator_id',
        'status',
        'total_amount',
        'note',
        'canceled_at',
        'completed_at',
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'canceled_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    /**
     * Relasi ke Supplier
     */
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    /**
     * Relasi ke Gudang Tujuan
     */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    /**
     * Relasi ke Operator / Staf yang membuat/mengelola PO
     */
    public function operator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'operator_id');
    }

    /**
     * Relasi ke Rincian Item Barang Pembelian (PO Items)
     */
    public function items(): HasMany
    {
        return $this->hasMany(PurchaseOrderItem::class, 'purchase_order_id');
    }
}
