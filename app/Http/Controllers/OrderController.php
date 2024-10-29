<?php

namespace App\Http\Controllers;

use App\Events\OrderPlaced;
use App\Http\Requests\OrderStoreRequest;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Models\Product;
use App\Pipes\CheckProductAvailabilityPipeline;
use Cache;
use DB;
use Illuminate\Http\JsonResponse;
use Illuminate\Pipeline\Pipeline;
use Symfony\Component\HttpKernel\Exception\HttpException;

class OrderController extends Controller
{
    public function store(OrderStoreRequest $request): ?JsonResponse
    {
        try {
            ['products' => $products, 'productQuantities' => $productQuantities] = app(Pipeline::class)
                ->send($request)
                ->through([
                    CheckProductAvailabilityPipeline::class,
                    // ValidateVoucherPipeline::class,
                    // Other pipelines...
                ])
                ->thenReturn();
        } catch (HttpException $e) {
            return response()->json(['message' => $e->getMessage()], $e->getStatusCode());
        }

        $order = DB::transaction(static function () use ($products, $request, $productQuantities) {
            $subtotal = $products->reduce(function ($carry, Product $product) use ($productQuantities) {
                $quantity = $productQuantities[$product->id] ?? 0;

                return $carry + (int) ($product->price->multiply($quantity)->getAmount());
            }, 0);

            $order = $request->user()->orders()->create([
                'subtotal' => $subtotal,
            ]);

            $order->products()->attach($products->map(function (Product $product) use ($productQuantities) {
                $quantity = $productQuantities[$product->id];

                return [
                    'product_id' => $product->id,
                    'quantity' => $quantity,
                    'total_price' => $product->price->multiply($quantity)->getAmount(),
                ];
            }));

            OrderPlaced::dispatch($order, $productQuantities);

            return $order;
        });

        return response()->json(['message' => 'Order placed successfully', 'data' => new OrderResource($order)], 201);

    }

    public function show(Order $order): OrderResource
    {
        return Cache::remember(
            "order.{$order->id}.{$order->user->id}",
            now()->addMinutes(10),
            static fn () => OrderResource::make($order)
        );
    }
}
