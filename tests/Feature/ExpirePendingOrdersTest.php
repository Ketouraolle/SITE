<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\Product;
use App\Services\CartService;
use App\Services\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExpirePendingOrdersTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['shop.pending_order_expiration_hours' => 48]);

        $this->freezeTime();
    }

    /**
     * @return array{0: Order, 1: Product}
     */
    private function placeOrder(): array
    {
        $product = Product::factory()->stock(10)->price(1000)->create();

        app(CartService::class)->addProduct($product, 3);

        $order = app(OrderService::class)->place([
            'customer_name' => 'Client Test',
            'customer_phone' => '699000000',
            'customer_address' => 'Douala',
        ]);

        return [$order, $product];
    }

    public function test_order_expires_at_48_hours_and_stock_is_released_only_once(): void
    {
        [$order, $product] = $this->placeOrder();

        $this->assertSame(7, $product->fresh()->stock);

        $this->travel(48)->hours();

        $this->artisan('shop:expire-pending-orders')
            ->assertExitCode(0);

        $this->assertSame(OrderStatus::Cancelled, $order->fresh()->status);
        $this->assertNotNull($order->fresh()->stock_released_at);
        $this->assertSame(10, $product->fresh()->stock);

        $this->artisan('shop:expire-pending-orders')
            ->assertExitCode(0);

        $this->assertSame(10, $product->fresh()->stock);
    }

    public function test_order_younger_than_48_hours_is_preserved(): void
    {
        [$order, $product] = $this->placeOrder();

        $this->travel(47)->hours();
        $this->travel(59)->minutes();

        $this->artisan('shop:expire-pending-orders')
            ->assertExitCode(0);

        $this->assertSame(OrderStatus::Pending, $order->fresh()->status);
        $this->assertNull($order->fresh()->stock_released_at);
        $this->assertSame(7, $product->fresh()->stock);
    }

    public function test_confirmed_processing_and_delivered_orders_are_preserved(): void
    {
        $records = [];

        foreach ([
            OrderStatus::Confirmed,
            OrderStatus::Processing,
            OrderStatus::Delivered,
        ] as $status) {
            [$order, $product] = $this->placeOrder();

            app(OrderService::class)->changeStatus($order, $status);

            $records[] = [$order, $product, $status];
        }

        $this->travel(72)->hours();

        $this->artisan('shop:expire-pending-orders')
            ->assertExitCode(0);

        foreach ($records as [$order, $product, $status]) {
            $this->assertSame($status, $order->fresh()->status);
            $this->assertNull($order->fresh()->stock_released_at);
            $this->assertSame(7, $product->fresh()->stock);
        }
    }

    public function test_invalid_expiration_delay_does_not_cancel_orders(): void
    {
        [$order, $product] = $this->placeOrder();

        $this->travel(72)->hours();

        config(['shop.pending_order_expiration_hours' => 0]);

        $this->artisan('shop:expire-pending-orders')
            ->assertExitCode(1);

        $this->assertSame(OrderStatus::Pending, $order->fresh()->status);
        $this->assertSame(7, $product->fresh()->stock);
    }
}
