<?php

namespace App\Models;

use App\Casts\Money;
use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Laravel\Scout\Searchable;

class Product extends Model
{
    /** @use HasFactory<ProductFactory> */
    use HasFactory, Searchable;

    protected $fillable = [
        'name',
        'description',
        'price',
        'category_id',
        'stock',
    ];

    public function toSearchableArray(): array
    {
        return $this->only('name');
    }

    /**
     * Get the stock activities for the product.
     */
    public function stockActivities(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(ProductStockActivity::class);
    }

    /**
     * Get the stock activities for the product that need restocking.
     */
    public function scopeNeedsRestock(\Illuminate\Database\Eloquent\Builder $query
    ): \Illuminate\Database\Eloquent\Builder {
        return $query->whereHas('stockActivities', function (\Illuminate\Database\Eloquent\Builder $query) {
            $query->where('needs_restock', true);
        });
    }

    public function orders(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Order::class);
    }

    /**
     * @return string[]
     */
    public function casts(): array
    {
        return [
            'price' => Money::class,
        ];
    }

    public function category(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function scopeCategory(Builder $builder, string $categoryName): Builder
    {
        return $builder->whereHas('category', function (Builder $builder) use ($categoryName) {
            $builder->where('name', $categoryName);
        });
    }

    public function scopePriceRange(Builder $builder, int $min, int $max): Builder
    {
        return $builder->whereBetween('price', [$min, $max]);
    }
}
