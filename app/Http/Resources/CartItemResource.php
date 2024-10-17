<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CartItemResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $sku = $this->variant ? $this->variant->sku : $this->product->sku;
        $price = $this->variant ? $this->variant->price : $this->product->price;
        $discount_price = $this->variant ? $this->variant->discount_price : $this->product->discount_price;
        $attributeValues = $this->variant ? $this->variant->attributeValues->map(function ($attributeValue) {
            return [
                'id' => $attributeValue->id,
                'value' => $attributeValue->value,
                'color_code' => $attributeValue->color_code
            ];
        }) : [];

        return [
            'product_id' => $this->product_id,
            'product_name' => $this->product->name,
            'product_image' => $this->product->image,
            'price' => $price,
            'sku' => $sku,
            'discount_price' => $discount_price,
            'quantity' => $this->quantity,
            'variant' => $this->variant ? [
                'variant_id' => $this->variant->id,
                'attributeValues' => $attributeValues
            ] : null,
        ];
    }
}
