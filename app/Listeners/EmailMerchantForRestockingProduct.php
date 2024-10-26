<?php

namespace App\Listeners;

use App\Events\ProductRanOutOfStock;
use Illuminate\Contracts\Queue\ShouldQueue;

class EmailMerchantForRestockingProduct implements ShouldQueue
{
    public string $connection = 'redis';

    public string $queue = 'listeners';

    /**
     * Handle the event.
     */
    public function handle(ProductRanOutOfStock $event): void
    {
        // $event->product->merchant->notify(new ProductRanOutOfStockNotification($event->product));
    }
}
