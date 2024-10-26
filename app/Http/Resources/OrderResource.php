<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'user' => UserResource::make($this->user),
            'sub_total' => $this->subtotal->divide(100),
            'status' => $this->status,
            'products' => ProductResource::collection($this->products),
            'created_at' => $this->created_at,
        ];
    }
}