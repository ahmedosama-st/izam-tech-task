<?php

namespace App\Jobs;

use App\Models\Product;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Queue\Queueable;

class UpdateProductStock implements ShouldQueueAfterCommit
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(public Collection $products, public array $productQuantities) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // NOTE: Bulk updating is possible here using native SQL cases
        $this->products->each(function (Product $product) {
            $product->decrement('stock', $this->productQuantities[$product->id]);
        });
    }
}
