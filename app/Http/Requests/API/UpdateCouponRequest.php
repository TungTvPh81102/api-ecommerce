<?php

namespace App\Http\Requests\API;

use App\Models\Coupon;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class UpdateCouponRequest extends FormRequest
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
        $coupon = $this->route('coupon');

        if (is_string($coupon)) {
            $coupon = Coupon::query()->find($coupon);
        }

        return [
            'name' => 'sometimes|required|string|min:3|max:255',
            'code' => 'sometimes|required|string|min:3|max:255|unique:coupons,code,' . ($coupon->id ?? ''),
            'type' => 'sometimes|required|in:fixed,percent',
            'value' => 'sometimes|required|numeric|min:0',
            'status' => 'sometimes|required|in:active,inactive',
            'start_date' => 'sometimes|required|date|after_or_equal:today',
            'expire_date' => 'sometimes|required|date|after:start_date',
            'max_discount_percentage' => 'required_if:type,percent|numeric|min:0',
            'min_order_amount' => 'sometimes|nullable|numeric|min:0',
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
