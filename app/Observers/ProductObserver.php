<?php

namespace App\Observers;

use App\Enums\ProductStockEnum;
use App\Models\Product;
use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;

class ProductObserver implements ShouldHandleEventsAfterCommit
{
    /**
     * Handle the Product "created" event.
     */
    public function created(Product $product): void
    {
        $product->stockActivities()->create([
            'stock' => $product->stock,
            'type' => ProductStockEnum::INITIAL,
            'needs_restock' => false,
        ]);
    }

    /**
     * Handle the Product "updated" event.
     */
    public function updated(Product $product): void
    {
        if ($product->isDirty('stock')) {
            $product->stockActivities()->create([
                'stock' => $product->stock,
                'type' => $this->getStockActivityType($product),
                'needs_restock' => $product->isBelowThreshold(),
            ]);
        }
    }

    protected function getStockActivityType(Product $product): ProductStockEnum
    {
        return $product->stock > $product->getOriginal('stock') ? ProductStockEnum::IN : ProductStockEnum::OUT;
    }
}
