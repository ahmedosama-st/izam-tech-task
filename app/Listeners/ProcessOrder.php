<?php

namespace App\Listeners;

use App\Events\OrderPlaced;
use App\Jobs\SyncProducts;
use App\Jobs\UpdateProductStock;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;

class ProcessOrder implements ShouldQueueAfterCommit
{
    /**
     * Handle the event.
     */
    public function handle(OrderPlaced $event): void
    {
        UpdateProductStock::dispatch($event->order->products, $event->productQuantities);
        SyncProducts::dispatch();
        // NOTE: We could fire another job UpdateProductStock::dispatch(). But we already have it handled via ProductStockObserver
        // We could also handle other jobs here like handling shipment, sending email, etc.
    }
}
