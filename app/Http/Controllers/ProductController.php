<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProductIndexRequest;
use App\Http\Requests\ProductStoreRequest;
use App\Http\Resources\ProductResource;
use App\Jobs\SyncProducts;
use App\Models\Product;
use Cache;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Arr;

class ProductController extends Controller
{
    public function index(ProductIndexRequest $request): AnonymousResourceCollection
    {
        $products = Product::search($request->input('name'), function ($meilisearch, $query, $options) use ($request) {
            $options['filter'] = $this->buildFilterOptions($request);
            $options['limit'] = $this->getPaginationLimit($request);
            $options['sort'] = ['created_at:desc'];

            return $meilisearch->search($query, $options);
        })->paginate($this->getPaginationLimit($request));

        $key = $this->getCacheKey($request, 'products.index');

        return Cache::remember($key, now()->addMinutes(10), fn () => ProductResource::collection($products));
    }

    protected function buildFilterOptions(ProductIndexRequest $request): string
    {
        $category = $request->get('filter')['category'] ?? null;
        $price = $request->get('filter')['price'] ?? null;
        $filters = [];

        if ($category) {
            $filters[] = 'category='.$category;
        }

        if ($price) {
            $price = explode(',', trim($price, '[]'));
            $filters[] = "price >= {$price[0]} AND price <= {$price[1]}";
        }

        $filters[] = 'stock > 0';

        return implode(' AND ', $filters);
    }

    protected function getCacheKey(Request $request, string $prefix): string
    {
        $query = $request->query();

        ksort($query);

        return $prefix.'.'.implode('_', Arr::flatten($query));
    }

    public function store(ProductStoreRequest $request): ProductResource
    {
        $product = Product::create($request->validated());

        SyncProducts::dispatch();

        return ProductResource::make($product);
    }

    /**
     * @return int|mixed
     */
    protected function getPaginationLimit(ProductIndexRequest $request): mixed
    {
        return $request->get('limit') ?? 10;
    }
}
