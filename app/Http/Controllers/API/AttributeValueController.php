<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\AttributeValue;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class AttributeValueController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try {
            $data = AttributeValue::query()
                ->with('attribute')
                ->latest('id')
                ->paginate();

            if (!$data->count()) {
                return response()->noContent();
            }

            return response()->json([
                'message' => 'Danh sách giá trị của thuộc tính',
                'data' => $data
            ]);
        } catch (\Exception $e) {
            Log::error(__CLASS__ . '@' . __FUNCTION__, [
                'message' => $e->getMessage(),
                'line' => $e->getLine(),
            ]);

            return response()->json([
                'message' => 'Lỗi server'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            $request->validate([
                'attribute_id' => 'required|exists:attributes,id',
                'value' => 'required|string|max:255|unique:attribute_values,value',
                'status' => 'required|in:active,inactive',
            ]);

            $attributeValue = AttributeValue::query()->create($request->all());

            if (!$attributeValue) {
                return response()->json([
                    'message' => 'Dữ liệu không hợp lệ'
                ], Response::HTTP_BAD_REQUEST);
            }

            return response()->json([
                'message' => 'Thao tác thành công',
                'data' => $attributeValue
            ], Response::HTTP_CREATED);
        } catch (\Exception $e) {
            Log::error(__CLASS__ . '@' . __FUNCTION__, [
                'message' => $e->getMessage(),
                'request' => $request->all(),
                'line' => $e->getLine(),
            ]);

            return response()->json([
                'message' => 'Lỗi server'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        try {
            $attributeValue = AttributeValue::query()->find($id);

            if (!$attributeValue) {
                return response()->json([
                    'message' => 'Không tìm thấy giá trị'
                ], Response::HTTP_NOT_FOUND);
            }

            $request->validate([
                'attribute_id' => 'sometimes|required|exists:attributes,id,' . $attributeValue->attribute_id,
                'value' => 'sometimes|required|string|max:255|unique:attribute_values,value,' . $attributeValue->id,
                'status' => 'sometimes|required|in:active,inactive',
            ]);

            $attributeValue->update($request->all());

            return response()->json([
                'message' => 'Thao tác thành công',
                'data' => $attributeValue
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            Log::error(__CLASS__ . '@' . __FUNCTION__, [
                'message' => $e->getMessage(),
                'request' => $request->all(),
                'line' => $e->getLine(),
            ]);

            return response()->json([
                'message' => 'Lỗi server'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        try {
            $attributeValue = AttributeValue::query()->find($id);

            if (!$attributeValue) {
                return response()->json([
                    'message' => 'Không tìm thấy giá trị'
                ], Response::HTTP_NOT_FOUND);
            }

            $attributeValue->delete();

            return response()->noContent();
        } catch (\Exception $e) {
            Log::error(__CLASS__ . '@' . __FUNCTION__, [
                'message' => $e->getMessage(),
                'line' => $e->getLine(),
            ]);

            return response()->json([
                'message' => 'Lỗi server'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
