<?php

namespace App\Pipes;

use App\Events\ProductRanOutOfStock;
use App\Models\Product;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

class CheckProductAvailabilityPipeline
{
    public function handle(Request $request, Closure $next)
    {
        $productQuantities = $this->getProductQuantities($request);

        $productIds = array_keys($productQuantities);

        $products = Product::whereIn('id', $productIds)
            ->lockForUpdate()
            ->get();

        $products->each(function (Product $product) use ($productQuantities) {
            if (! $product->canBeOrdered($productQuantities[$product->id])) {
                event(new ProductRanOutOfStock($product));

                throw new HttpException(400,
                    "Insufficient stock for product {{$product->name}} requested {$productQuantities[$product->id]} but only {$product->stock} available.");
            }
        });

        return $next(['products' => $products, 'productQuantities' => $productQuantities]);
    }

    protected function getProductQuantities(Request $request): array
    {
        return collect($request->validated('products'))->mapWithKeys(function ($item) {
            return [$item['id'] => $item['quantity']];
        })->all();
    }
}
