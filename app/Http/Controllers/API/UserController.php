<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\API\StoreUserRequest;
use App\Http\Requests\API\UpdateUserRequest;
use App\Models\User;
use Cloudinary\Cloudinary;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class UserController extends Controller
{
    protected $cloudinary;
    const FOLDER_NAME = 'users';

    public function __construct()
    {
        $this->cloudinary = new Cloudinary();
    }

    /**
     * [GET]: /api/users - Danh sách người dùng trên hệ thống
     */
    public function index()
    {
        try {
            $data = User::query()->latest('id')->paginate();

            if (!$data) {
                return response()->json([
                    'message' => 'Không có dữ liệu'
                ], Response::HTTP_NOT_FOUND);
            }

            return response()->json([
                'message' => 'Danh sách người dùng',
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

    /**
     * [POST]: /api/users - Thêm mới người dùng
     */
    public function store(StoreUserRequest $request)
    {
        try {
            DB::beginTransaction();

            $data = $request->validated();

            if (!$data) {
                return response()->json([
                    'message' => 'Dữ liệu không hợp lệ vui lòng thử lại'
                ], Response::HTTP_BAD_REQUEST);
            }

            if ($request->hasFile('avatar')) {
                $avatar = $request->file('avatar');

                if ($avatar->isValid()) {
                    $uploadResult = $this->cloudinary
                        ->uploadApi()
                        ->upload($avatar->getRealPath(), [
                            'folder' => self::FOLDER_NAME
                        ]);

                    if (!$data['avatar']) {
                        return response()->json([
                            'message' => 'Upload ảnh không thành công'
                        ], Response::HTTP_BAD_REQUEST);
                    }

                    $data['avatar'] = $uploadResult['secure_url'];
                } else {
                    return response()->json([
                        'message' => 'Dữ liệu ảnh không hợp lệ, vui lòng thử lại'
                    ], Response::HTTP_BAD_REQUEST);
                }
            }

            $user = User::query()->create($data);

            DB::commit();

            return response()->json([
                'mesage' => 'Thao tác thành công',
                'data' => $user
            ], Response::HTTP_CREATED);
        } catch (\Exception $e) {
            DB::rollBack();

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
     * [GET]: /api/users/{id} - Thông tin nguười dùng
     */
    public function show(string $id)
    {
        try {
            $user = User::query()->find($id);

            if (!$user) {
                return response()->json([
                    'message' => 'Không tìm thấy người dùng'
                ], Response::HTTP_NOT_FOUND);
            }

            return response()->json([
                'message' => 'Thông tin người dùng: ' . $user->name,
                'data' => $user
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

    /**
     * [POST]: /api/users/{id}/update - Cập nhật thông tin ngời dun
     */
    public function update(UpdateUserRequest $request, string $id)
    {
        try {
            DB::beginTransaction();

            $user = User::query()->find($id);

            if (!$user) {
                return response()->json([
                    'message' => 'Không tìm thấy người dùng'
                ], Response::HTTP_NOT_FOUND);
            }

            $data = $request->validated();

            if ($request->hasFile('avatar')) {
                $oldAvatar = $user->avatar;
                $avatar = $request->file('avatar');

                if ($oldAvatar) {
                    $publicId = pathinfo(parse_url($oldAvatar, PHP_URL_PATH), PATHINFO_FILENAME);
                    $publicIdWithFolder = self::FOLDER_NAME . '/' . $publicId;
                    $this->cloudinary
                        ->uploadApi()
                        ->destroy($publicIdWithFolder);
                }

                if ($avatar->isValid()) {
                    $uploadResult = $this->cloudinary
                        ->uploadApi()
                        ->upload($avatar->getRealPath(), [
                            'folder' => self::FOLDER_NAME
                        ]);

                    if (!$data['avatar']) {
                        return response()->json([
                            'message' => 'Upload ảnh không thành công'
                        ], Response::HTTP_BAD_REQUEST);
                    }

                    $data['avatar'] = $uploadResult['secure_url'];
                } else {
                    return response()->json([
                        'message' => 'Dữ liệu ảnh không hợp lệ, vui lòng thử lại'
                    ], Response::HTTP_BAD_REQUEST);
                }
            } else {
                $data['avatar'] = $user->avatar;
            }


            $user->update($data);

            DB::commit();
            return response()->json([
                'message' => 'Thao tác thành công',
                'data' => $user
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            DB::rollBack();

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
     * [DELETE]: /api/users/{id} - Xoá người dùng - Sort Delete
     */
    public function destroy(string $id)
    {
        try {
            $user = User::query()->find($id);

            if (!$user) {
                return response()->json([
                    'message' => 'Không tìm thấy người dùng'
                ], Response::HTTP_NOT_FOUND);
            }

            $user->delete();

            return response()->json([
                'message' => 'Thao tác thành công',
            ], Response::HTTP_NO_CONTENT);
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
     * [GET]: /api/users/restore/{id} - Khôi phục dữ liệu
     */
    public function restore(string $id)
    {
        try {
            $user = User::query()
                ->withTrashed()
                ->find($id);

            if (!$user) {
                return response()->json([
                    'message' => 'Không tìm thấy người dùng'
                ], Response::HTTP_NOT_FOUND);
            }

            if (!$user->trashed()) {
                return response()->json([
                    'message' => 'Người dùng không bị xóa, không cần phục hồi'
                ], Response::HTTP_BAD_REQUEST);
            }

            $user->restore();

            return response()->json([
                'message' => 'Thao tác thành công',
                'data' => $user
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

    /**
     * [DELETE]: /api/users/{id} - Xoá người dùng khỏi hệ thống
     */
    public function forceDestroy(string $id)
    {
        try {
            $user = User::query()
                ->withTrashed()
                ->find($id);

            if (!$user) {
                return response()->json([
                    'message' => 'Không tìm thấy người dùng'
                ], Response::HTTP_NOT_FOUND);
            }

            $user->forceDelete();

            if ($user->avatar) {
                $publicId = pathinfo(parse_url($user->avatar, PHP_URL_PATH), PATHINFO_FILENAME);
                $publicIdWithFolder = self::FOLDER_NAME . '/' . $publicId;
                $this->cloudinary
                    ->uploadApi()
                    ->destroy($publicIdWithFolder);
            }

            return response()->json([
                'message' => 'Thao tác thành công',
            ], Response::HTTP_NO_CONTENT);
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
