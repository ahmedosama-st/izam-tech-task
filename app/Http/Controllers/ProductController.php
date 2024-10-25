<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProductSearchRequest;
use App\Http\Requests\ProductStoreRequest;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use Cache;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use JsonException;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class ProductController extends Controller
{
    /**
     * @throws JsonException
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = QueryBuilder::for(Product::class)
            ->with(['category'])
            ->allowedFilters([
                AllowedFilter::scope('price_range'),
                AllowedFilter::scope('category'),
            ])
            ->allowedSorts(['price', 'created_at'])
            ->paginate(50);
        $key = $this->getCacheKey($request, 'products.index');

        return Cache::remember($key, now()->addMinutes(10), static fn () => ProductResource::collection($query));
    }

    /**
     * @throws JsonException
     */
    protected function getCacheKey(Request $request, string $prefix): string
    {
        return $prefix.'.'.md5(json_encode($request->query(), JSON_THROW_ON_ERROR));
    }

    public function store(ProductStoreRequest $request): JsonResponse
    {
        $product = Product::create($request->validated());

        return response()->json($product, 201);
    }

    /**
     * @throws JsonException
     */
    public function search(ProductSearchRequest $request): AnonymousResourceCollection
    {
        $name = $request->input('name');
        $products = Product::search($name)->paginate(10);
        $key = $this->getCacheKey($request, 'products.search');

        return Cache::remember($key, now()->addMinutes(10), static fn () => ProductResource::collection($products));
    }
}
