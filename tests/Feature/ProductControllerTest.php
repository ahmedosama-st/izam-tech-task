<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
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
        Product::factory(3)->create();

        $response = $this->json('GET', '/api/products');

        $response->assertStatus(200);
        $response->assertJsonCount(3, 'data');
    }

    #[Test]
    #[NoReturn]
    public function it_allows_filtering_by_category(): void
    {
        // Arrange
        $category = Category::factory()->create(['name' => 'testing_category']);
        Product::factory()->create([
            'category_id' => $category->id,
        ]);

        // Act
        $response = $this->json('GET', '/api/products', [
            'filter' => [
                'category' => 'testing_category',
            ]
        ]);

        // Assert
        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');
    }

    #[Test]
    #[NoReturn]
    public function it_allows_filtering_by_price_range(): void
    {
        // Arrange
        Product::factory()->create([
            'price' => 6000
        ]);

        Product::factory(2)->create();

        // Act
        $response = $this->json('GET', '/api/products', [
            'filter' => [
                'price_range' => [2000, 10000]
            ]
        ]);

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
    public function it_applies_searching(): void
    {
        // Arrange
        Product::factory()->create([
            'name' => 'Product 1',
        ]);

        // Act
        $response = $this->json('GET', '/api/products', [
            'name' => 'P',
        ]);

        // Assert
        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');
    }
}
