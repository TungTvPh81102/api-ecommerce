<?php

namespace App\Http\Requests\API;

use App\Models\User;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class StoreUserRequest extends FormRequest
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
        return [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
            'avatar' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:255',
            'phone' => 'nullable|min:10|max:13|regex:/^([0-9\s\-\+\(\)]*)$/|unique:users',
            'status' => 'required|in:0,1',
            'role' => [
                'required',
                Rule::in([User::ROLE_ADMIN, User::ROLE_MEMBER, User::ROLE_STAFF]),
            ]
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
