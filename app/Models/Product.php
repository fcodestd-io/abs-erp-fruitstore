<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'cost_price',
        'selling_price',
        'store_unit_id',
        'warehouse_unit_id',
    ];

    public function storeUnit(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'store_unit_id');
    }

    public function warehouseUnit(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'warehouse_unit_id');
    }
}
