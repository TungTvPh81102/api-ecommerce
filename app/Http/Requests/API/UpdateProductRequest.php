<?php

namespace App\Http\Requests\API;

use App\Models\Product;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class UpdateProductRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $product = $this->route('product');

        if (is_string($product)) {
            $product = Product::query()->find($product);
        }

        return [
            'category_id' => 'sometimes|required|exists:categories,id',
            'sku' => 'sometimes|required|string|min:3|max:255|unique:products,sku,' . ($product->id ?? ''),
            'name' => 'sometimes|required|string|min:3|max:255',
            'price' => 'sometimes|required|numeric|min:0|regex:/^\d+(\.\d{1,2})?$/',
            'discount_price' => 'nullable|numeric|min:0|regex:/^\d+(\.\d{1,2})?$/',
            'stock' => 'nullable|numeric|min:0',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,webp,svg|max:2048',
            'status' => 'sometimes|required|in:active,inactive',
            'description' => 'nullable|string',
            'content' => 'nullable|string',
            'attributes' => 'sometimes|required|array',
            'attributes.*' => 'exists:attributes,id',
            'variants' => 'sometimes|required|array',
            'variants.*.sku' => 'required|string|min:3|max:255|unique:products,sku',
            'variants.*.price' => 'required|numeric|min:0',
            'variants.*.discount_price' => 'nullable|numeric|min:0',
            'variants.*.stock' => 'required|numeric|min:0',
            'variants.*.image.*' => 'nullable|image|mimes:jpeg,png,jpg,webp,svg',
            'variants.*.attribute_values' => 'required|array|min:1',
            'variants.*.attribute_values.*.id' => 'required|exists:attribute_values,id',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        $errors = $validator->errors();

        $response = response()->json([
            'message' => $errors->messages()
        ], Response::HTTP_BAD_REQUEST);

        Log::error(__CLASS__ . '@' . __FUNCTION__, [
            'message' => $errors->messages()
        ]);

        throw new HttpResponseException($response);
    }
}
