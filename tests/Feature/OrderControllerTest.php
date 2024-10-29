<?php

namespace Tests\Feature;

use App\Enums\ProductStockEnum;
use App\Events\ProductRanOutOfStock;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use JetBrains\PhpStorm\NoReturn;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class OrderControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    #[NoReturn]
    public function it_shows_an_order(): void
    {
        // Arrange
        $user = User::factory()->create();
        $products = Product::factory(2)->create([
            'price' => 500,
        ]);

        $order = $user->orders()->create([
            'subtotal' => 1000,
        ]);

        $order->products()->attach($products->map(function (Product $product) {
            return [
                'product_id' => $product->id,
                'quantity' => 1,
                'total_price' => $product->price->getAmount(),
            ];
        }));

        // Act
        $response = $this->actingAs($user)->json('GET', '/api/orders/'.$order->id);

        // Assert
        $response->assertStatus(200);
    }

    #[Test]
    #[NoReturn]
    public function it_validates_products_from_request(): void
    {
        // Arrange
        $user = User::factory()->create();
        Product::factory()->create();
        $requestBody = [
            'products' => [
                [
                    'id' => 'testing',
                ],
            ],
        ];

        // Act
        $response = $this->actingAs($user)->json('POST', '/api/orders/create', $requestBody);

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors([
            'products.0.id',
            'products.0.quantity',
        ]);
    }

    #[Test]
    #[NoReturn]
    public function it_checks_for_product_availability_and_fires_an_event_if_product_is_out_of_stock(): void
    {
        // Arrange
        Event::fake();
        $user = User::factory()->create();
        $product = Product::factory()->create([
            'stock' => 10,
        ]);
        $requestBody = [
            'products' => [
                [
                    'id' => $product->id,
                    'quantity' => 100,
                ],
            ],
        ];

        // Act
        $response = $this->actingAs($user)->json('POST', '/api/orders/create', $requestBody);

        // Assert
        $response->assertStatus(400);
        $response->assertJsonFragment([
            'message' => "Insufficient stock for product {{$product->name}} requested {$requestBody['products'][0]['quantity']} but only {$product->stock} available.",
        ]);
        Event::assertDispatched(ProductRanOutOfStock::class, static fn ($event) => $event->product->is($product));
    }

    #[Test]
    #[NoReturn]
    public function it_fires_order_placed_event_and_handles_order(): void
    {
        // Arrange
        $user = User::factory()->create();
        $productOne = Product::factory()->create([
            'stock' => 10,
            'price' => 1500,
        ]);
        $productTwo = Product::factory()->create([
            'stock' => 5,
            'price' => 2500,
        ]);
        $requestBody = [
            'products' => [
                [
                    'id' => $productOne->id,
                    'quantity' => 2,
                ],
                [
                    'id' => $productTwo->id,
                    'quantity' => 1,
                ],
            ],
        ];

        // Act
        $response = $this->actingAs($user)->json('POST', '/api/orders/create', $requestBody);

        // Assert
        $response->assertJsonFragment([
            'message' => 'Order placed successfully',
        ]);
    }

    #[Test]
    #[NoReturn]
    public function it_calculates_subtotal_correctly(): void
    {
        // Assert
        $user = User::factory()->create();
        $productOne = Product::factory()->create([
            'stock' => 10,
            'price' => 1500,
        ]);
        $productTwo = Product::factory()->create([
            'stock' => 5,
            'price' => 2500,
        ]);
        $requestBody = [
            'products' => [
                [
                    'id' => $productOne->id,
                    'quantity' => 2,
                ],
                [
                    'id' => $productTwo->id,
                    'quantity' => 1,
                ],
            ],
        ];

        // Act
        $this->actingAs($user)->json('POST', '/api/orders/create', $requestBody);

        // Assert
        $this->assertDatabaseHas('orders', [
            'subtotal' => 5500,
        ]);
    }

    #[Test]
    #[NoReturn]
    public function it_attaches_products_to_order(): void
    {
        // Assert
        $user = User::factory()->create();
        $productOne = Product::factory()->create([
            'stock' => 10,
            'price' => 1500,
        ]);
        $productTwo = Product::factory()->create([
            'stock' => 5,
            'price' => 2500,
        ]);
        $requestBody = [
            'products' => [
                [
                    'id' => $productOne->id,
                    'quantity' => 2,
                ],
                [
                    'id' => $productTwo->id,
                    'quantity' => 1,
                ],
            ],
        ];

        // Act
        $this->actingAs($user)->json('POST', '/api/orders/create', $requestBody);

        // Assert
        $this->assertDatabaseHas('order_product', [
            'product_id' => $productOne->id,
            'quantity' => 2,
            'total_price' => 3000,
        ]);
    }

    #[Test]
    #[NoReturn]
    public function it_decreases_product_stock_by_requested_quantity(): void
    {
        // Assert
        $user = User::factory()->create();
        $productOne = Product::factory()->create([
            'stock' => 10,
            'price' => 1500,
        ]);
        $productTwo = Product::factory()->create([
            'stock' => 5,
            'price' => 2500,
        ]);
        $requestBody = [
            'products' => [
                [
                    'id' => $productOne->id,
                    'quantity' => 2,
                ],
                [
                    'id' => $productTwo->id,
                    'quantity' => 1,
                ],
            ],
        ];

        // Act
        $this->actingAs($user)->json('POST', '/api/orders/create', $requestBody);

        // Assert
        $this->assertDatabaseHas('products', [
            'id' => $productOne->id,
            'stock' => 8,
        ]);
    }

    #[Test]
    #[NoReturn]
    public function it_keeps_track_of_product_stock_activity(): void
    {
        // Assert
        $user = User::factory()->create();
        $productOne = Product::factory()->create([
            'stock' => 10,
            'price' => 1500,
        ]);
        $productTwo = Product::factory()->create([
            'stock' => 5,
            'price' => 2500,
        ]);
        $requestBody = [
            'products' => [
                [
                    'id' => $productOne->id,
                    'quantity' => 2,
                ],
                [
                    'id' => $productTwo->id,
                    'quantity' => 1,
                ],
            ],
        ];

        // Act
        $this->actingAs($user)->json('POST', '/api/orders/create', $requestBody);

        // Assert
        $this->assertDatabaseHas('product_stock_activities', [
            'product_id' => $productOne->id,
            'stock' => 8,
            'type' => ProductStockEnum::OUT,
        ]);
    }

    #[Test]
    #[NoReturn]
    public function it_doesnt_allow_order_creation_for_unauthenticated_users(): void
    {
        // Arrange
        $product = Product::factory()->create();

        // Act
        $response = $this->json('POST', '/api/orders/create', [
            'products' => [
                [
                    'id' => $product->id,
                    'quantity' => 1,
                ],
            ],
        ]);

        // Assert
        $response->assertStatus(401);
    }

    #[Test]
    #[NoReturn]
    public function it_marks_product_as_needs_restock_if_stock_is_below_threshold(): void
    {
        // Arrange
        $user = User::factory()->create();
        $product = Product::factory()->create([
            'stock' => 5,
            'price' => 1500,
        ]);
        $requestBody = [
            'products' => [
                [
                    'id' => $product->id,
                    'quantity' => 5,
                ],
            ],
        ];

        // Act
        $this->actingAs($user)->json('POST', '/api/orders/create', $requestBody);

        // Assert
        $this->assertDatabaseHas('product_stock_activities', [
            'product_id' => $product->id,
            'stock' => 0,
            'needs_restock' => true,
            'type' => ProductStockEnum::OUT,
        ]);
    }
}
