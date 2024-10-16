<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\API\StoreCategoryRequest;
use App\Http\Requests\API\UpdateCategoryRequest;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class CategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try {
            $data = Category::query()
                ->with(['parent'])
                ->latest('id')
                ->paginate();

            if (!$data) {
                return response()->json([
                    'message' => 'Không có dữ liệu'
                ], Response::HTTP_NOT_FOUND);
            }

            return response()->json([
                'message' => 'Danh sách danh mục',
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
     * Store a newly created resource in storage.
     */
    public function store(StoreCategoryRequest $request)
    {
        try {
            $data = $request->validated();

            if (!$data) {
                return response()->json([
                    'message' => 'Dữ liệu không hợp lệ'
                ], Response::HTTP_BAD_REQUEST);
            }

            $data['slug'] = $data['name'] ? Str::slug($data['name'], '-') : null;

            $category = Category::query()->create($data);

            return response()->json([
                'message' => 'Thao tác thành công',
                'data' => $category
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
        try {
            $data = Category::query()
                ->with('parent')
                ->find($id);

            if (!$data) {
                return response()->json([
                    'message' => 'Không có dữ liệu'
                ], Response::HTTP_NOT_FOUND);
            }

            return response()->json([
                'message' => 'Chi tiết danh mục: ' . $data->name,
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
     * Update the specified resource in storage.
     */
    public function update(UpdateCategoryRequest $request, string $id)
    {
        try {
            $category = Category::query()
                ->with('parent')
                ->find($id);

            if (!$category) {
                return response()->json([
                    'message' => 'Không có dữ liệu'
                ], Response::HTTP_NOT_FOUND);
            }

            $data = $request->validated();

            if (!$data) {
                return response()->json([
                    'message' => 'Dữ liệu không hợp lệ'
                ], Response::HTTP_BAD_REQUEST);
            }

            if (isset($data['name'])) {
                $data['slug'] = Str::slug($data['name'], '-');
            } else {
                $data['slug'] = $category->slug;
            }

            $category->update($data);

            $category->refresh();

            return response()->json([
                'message' => 'Thao tác thành công',
                'data' => $category
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
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        try {
            $category = Category::query()->find($id);

            if (!$category) {
                return response()->json([
                    'message' => 'Không có dữ liệu'
                ], Response::HTTP_NOT_FOUND);
            }

            $parentCategory = Category::query()
                ->where('parent_id', $category->id)
                ->count();

            if ($parentCategory > 0) {
                return response()->json([
                    'message' => 'Không thể xoá danh mục, vui lòng thử lại'
                ], Response::HTTP_BAD_REQUEST);
            }

            $category->delete();

            return response()->json([
                'message' => 'Thao tác thành công'
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
