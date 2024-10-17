<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class DashboardController extends Controller
{
    public function search(Request $request)
    {
        try {
            $query = $request->q;

            if (empty($query)) {
                return response()->json([
                    'message' => 'Dữ liệu không hợp lệ'
                ], Response::HTTP_BAD_REQUEST);
            }

            if (strlen($query) < 3) {
                $data = Product::query()
                    ->with('category')
                    ->where('name', 'like', '%' . $query . '%')
                    ->orWhere('description', 'like', '%' . $query . '%')
                    ->get();
            } else {
                $data = Product::query()
                    ->with('category')
                    ->whereFullText('name', $query)
                    ->orWhereFullText('description', $query)
                    ->get();
            }

            if (!$data->count()) {
                return response()->json([
                    'message' => 'Không có dữ liệu'
                ], Response::HTTP_NOT_FOUND);
            }

            return response()->json([
                'message' => 'Thao tác thành công',
                'data' => $data
            ], Response::HTTP_OK);
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
