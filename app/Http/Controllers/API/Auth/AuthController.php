<?php

namespace App\Http\Controllers\API\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\API\LoginRequest;
use App\Http\Requests\API\RegisterRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Response;

class AuthController extends Controller
{
    public function register(RegisterRequest $request)
    {
        try {
            $data = $request->validated();

            if (!$data) {
                return response()->json([
                    'message' => 'Dữ liệu không hợp lệ, vui lòng thử lại'
                ], Response::HTTP_BAD_REQUEST);
            }

            $user = User::query()->create($data);

            if (!$user) {
                return response()->json([
                    'message' => 'Đăng ký thất bại, vui lòng thử lại'
                ], Response::HTTP_NOT_FOUND);
            }

            $token = $user->createToken($user->id)->plainTextToken;

            return response()->json([
                'message' => 'Đăng ký thành công',
                'access_token' => $token,
                'data' => $user,
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

    public function login(LoginRequest $request)
    {
        try {
            $data = $request->validated();

            if (!$data) {
                return response()->json([
                    'message' => 'Dữ liệu không hợp lệ, vui lòng thử lại'
                ], Response::HTTP_BAD_REQUEST);
            }

            $user = User::query()
                ->where('email', $data['email'])
                ->first();

            if (!$user || !Hash::check($data['password'], $user->password)) {
                return response()->json([
                    'message' => 'Tài khoản mật khẩu không chính xác, vui lòng thử lại'
                ], Response::HTTP_NOT_FOUND);
            }

            $token = $user->createToken($user->id)->plainTextToken;

            return response()->json([
                'message' => 'Đăng nhập thành công',
                'access_token' => $token,
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

    public function logout(Request $request)
    {
        try {
            $request->user()->currentAccessToken()->delete();

            return response()->json([
                'message' => 'Đăng xuất thành công'
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
}
