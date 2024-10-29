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

    public const PRODUCT_STOCK_PERCENTAGE_THRESHOLD = 0.2;

    protected $fillable = [
        'name',
        'description',
        'price',
        'category_id',
        'stock',
    ];

    public function toSearchableArray(): array
    {
        return [
            'name' => $this->name,
            'description' => $this->description,
            'price' => (int) $this->price->getAmount(),
            'stock' => $this->stock,
            'created_at' => $this->created_at,
            'category' => $this->category->name,
        ];
    }

    /**
     * Get the stock activities for the product.
     */
    public function stockActivities(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(ProductStockActivity::class);
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

    public function scopePrice(Builder $builder, int $min, int $max): Builder
    {
        return $builder->whereBetween('price', [$min, $max]);
    }

    public function canBeOrdered(int $quantity): bool
    {
        return ($this->stock - $quantity) >= 0;
    }

    public function isBelowThreshold(): bool
    {
        return $this->stock < ((int) ceil($this->stockActivities()->initial()->stock * self::PRODUCT_STOCK_PERCENTAGE_THRESHOLD));
    }
}
