<?php

namespace Tests\Feature;

use App\Enums\ProductStockEnum;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Cache;
use Illuminate\Foundation\Testing\RefreshDatabase;
use JetBrains\PhpStorm\NoReturn;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ProductControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    #[NoReturn]
    public function it_lists_all_products(): void
    {
        Product::factory()->create();

        $response = $this->json('GET', '/api/products');

        $response->assertStatus(200);
    }

    #[Test]
    #[NoReturn]
    public function it_allows_filtering_by_category(): void
    {
        $category = Category::factory()->create(['name' => 'testing_category']);
        Product::factory()->create([
            'category_id' => $category->id,
        ]);

        // Act
        $response = $this->json('GET', '/api/products', [
            'filter' => [
                'category' => 'testing_category',
            ],
        ]);

        // Assert
        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');
    }

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
    }

    #[Test]
    #[NoReturn]
    public function it_allows_filtering_by_price_range(): void
    {
        // Arrange
        Product::factory()->create([
            'price' => 6000,
        ]);

        Product::factory(2)->create();

        // Act
        $response = $this->getJson('/api/products?filter[price]=5000,7000');

        // Assert
        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');
    }

    #[Test]
    #[NoReturn]
    public function it_validates_products_data(): void
    {
        // Arrange
        $user = User::factory()->create();
        $requestBody = [
            'name' => 'Product 1',
            'description' => 'Product 1 description',
            'price' => 1000,
            'category_id' => Category::factory()->create()->id,
            'stock' => 10,
        ];

        // Act
        $response = $this->actingAs($user)->json('POST', '/api/products/create', $requestBody);

        // Assert
        $response->assertStatus(201);
        $this->assertDatabaseHas('products', ['name' => 'Product 1']);
    }

    #[Test]
    #[NoReturn]
    public function it_creates_product_stock_activities(): void
    {
        // Arrange
        $user = User::factory()->create();
        $requestBody = [
            'name' => 'Product 1',
            'description' => 'Product 1 description',
            'price' => 1000,
            'category_id' => Category::factory()->create()->id,
            'stock' => 10,
        ];

        // Act
        $response = $this->actingAs($user)->json('POST', '/api/products/create', $requestBody);

        // Assert
        $response->assertStatus(201);
        $this->assertDatabaseHas('products', ['name' => 'Product 1']);

        $product = Product::where('name', 'Product 1')->first();
        $this->assertDatabaseHas('product_stock_activities', [
            'product_id' => $product->id,
            'stock' => $product->stock,
            'type' => ProductStockEnum::INITIAL,
            'needs_restock' => false,
        ]);
    }

    #[Test]
    #[NoReturn]
    public function it_applies_searching(): void
    {
        // Arrange
        Product::factory()->create([
            'name' => 'Testing product',
        ]);

        // Act
        $response = $this->getJson('/api/products?name=ahmed');

        // Assert
        $response->assertStatus(200);
        $response->assertJsonCount(0, 'data');
    }
}
