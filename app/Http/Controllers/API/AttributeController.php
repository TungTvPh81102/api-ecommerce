<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Attribute;
use F9Web\ApiResponseHelpers;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class AttributeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try {
            $data = Attribute::query()->latest('id')->paginate();

            if (!$data->count()) {
                return response()->json([
                    'message' => 'Không có dữ liệu'
                ], Response::HTTP_NO_CONTENT);
            }

            return response()->json([
                'message' => 'Danh sách thuộc tính',
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
                'name' => 'required|string|max:255|unique:attributes',
            ]);

            $attribute = Attribute::query()->create($request->all());

            if (!$attribute) {
                return response()->json([
                    'message' => 'Dữ liệu không hợp lệ'
                ], Response::HTTP_BAD_REQUEST);
            }

            return response()->json([
                'message' => 'Thao tác thành công',
                'data' => $attribute
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
            $attribute = Attribute::query()->find($id);

            if (!$attribute) {
                return response()->json([
                    'message' => 'Không tìm thấy thuộc tính'
                ], Response::HTTP_NOT_FOUND);
            }

            $attribute->update($request->all());

            return response()->json([
                'message' => 'Thao tác thành công',
                'data' => $attribute
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
            $attribute = Attribute::query()->find($id);

            if (!$attribute) {
                return response()->json([
                    'message' => 'Không tìm thấy thuộc tính'
                ], Response::HTTP_NOT_FOUND);
            }

            $attribute->delete();

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
