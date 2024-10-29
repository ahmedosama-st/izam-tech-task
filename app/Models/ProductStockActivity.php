<?php

namespace App\Models;

use App\Enums\ProductStockEnum;
use Database\Factories\ProductStockActivityFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductStockActivity extends Model
{
    /** @use HasFactory<ProductStockActivityFactory> */
    use HasFactory;

    protected $fillable = ['stock', 'type', 'needs_restock'];

    protected $casts = [
        'type' => ProductStockEnum::class,
        'needs_restock' => 'boolean',
        'stock' => 'integer',
    ];

    /**
     * Get the product that owns the stock activity.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function scopeInitial(Builder $query)
    {
        return $query->where('type', ProductStockEnum::INITIAL)->first();
    }
}
