<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\API\StoreCouponRequest;
use App\Http\Requests\API\UpdateCouponRequest;
use App\Models\Coupon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class CouponController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try {
            $data = Coupon::query()->latest('id')->paginate();

            if (!$data->count()) {
                return response()->json([
                    'message' => 'Không có dữ liệu'
                ], Response::HTTP_NOT_FOUND);
            }

            return response()->json([
                'message' => 'Danh sách mã giảm giá',
                'data' => $data
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            Log::error(__CLASS__ . '@' . __FUNCTION__, [
                'message' => $e->getMessage(),
                'line' => $e->getLine()
            ]);

            return response()->json([
                'message' => 'Lỗi server'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreCouponRequest $request)
    {
        try {
            $data = $request->validated();

            if (empty($data)) {
                return response()->json([
                    'message' => 'Dữ liệu không hợp lệ'
                ], Response::HTTP_BAD_REQUEST);
            }

            $data = Coupon::query()->create($data);

            return response()->json([
                'message' => 'Thao tác thành công',
                'data' => $data
            ], Response::HTTP_CREATED);
        } catch (\Exception $e) {
            Log::error(__CLASS__ . '@' . __FUNCTION__, [
                'message' => $e->getMessage(),
                'request' => $request->all(),
                'line' => $e->getLine()
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
        try {
            $coupon = Coupon::query()->find($id);

            if (empty($coupon)) {
                return response()->json([
                    'message' => 'Không có dữ liệu'
                ], Response::HTTP_NOT_FOUND);
            }

            return response()->json([
                'message' => 'Thông tin mã giảm giá: ' . $coupon->name,
                'data' => $coupon
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            Log::error(__CLASS__ . '@' . __FUNCTION__, [
                'message' => $e->getMessage(),
                'line' => $e->getLine()
            ]);

            return response()->json([
                'message' => 'Lỗi server'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateCouponRequest $request, string $id)
    {
        try {
            $coupon = Coupon::query()->find($id);

            if (empty($coupon)) {
                return response()->json([
                    'message' => 'Không có dữ liệu'
                ], Response::HTTP_NOT_FOUND);
            }

            $data = $request->validated();

            if (empty($data)) {
                return response()->json([
                    'message' => 'Dữ liệu không hợp lệ'
                ], Response::HTTP_BAD_REQUEST);
            }

            if ($data['type'] == 'fixed' && !empty($data['type'])) {
                $data['max_discount_percentage'] = null;
            }
            $coupon->update($data);

            return response()->json([
                'message' => 'Thao tác thành công',
                'data' => $coupon
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            Log::error(__CLASS__ . '@' . __FUNCTION__, [
                'message' => $e->getMessage(),
                'line' => $e->getLine()
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
            $coupon = Coupon::query()->find($id);

            if (empty($coupon)) {
                return response()->json([
                    'message' => 'Không có dữ liệu'
                ], Response::HTTP_NOT_FOUND);
            }

            $coupon->delete();

            return response()->noContent();
        } catch (\Exception $e) {
            Log::error(__CLASS__ . '@' . __FUNCTION__, [
                'message' => $e->getMessage(),
                'line' => $e->getLine()
            ]);

            return response()->json([
                'message' => 'Lỗi server'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
