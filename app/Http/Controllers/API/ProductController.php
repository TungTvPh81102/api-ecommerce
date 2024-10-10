<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\API\StoreProductRequest;
use App\Http\Requests\API\UpdateProductRequest;
use App\Models\Product;
use Cloudinary\Cloudinary;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    protected $cloudinary;

    const FOLDER_NAME = 'products';
    const FOLODER_NAME_VARIANT = 'variants';

    public function __construct()
    {
        $this->cloudinary = new Cloudinary();
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try {
            $data = Product::query()
                ->with('category')
                ->latest('id')
                ->paginate();

            if (!$data->count()) {
                return response()->json([
                    'message' => 'Không có dữ liệu'
                ], Response::HTTP_NOT_FOUND);
            }

            return response()->json([
                'message' => 'Danh sách sản phẩm',
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
    public function store(StoreProductRequest $request)
    {
        try {
            DB::beginTransaction();
            $dataProduct = $request->except('variants', 'image');
            $dataVariant = $request->variants;
            $dataProduct['slug'] = $dataProduct['name'] ? Str::slug($dataProduct['name'], '-') : null;

            $image = $request->file('image') ?? null;

            if ($image->isValid()) {
                $uploadResult = $this->cloudinary
                    ->uploadApi()
                    ->upload($image->getRealPath(), [
                        'folder' => self::FOLDER_NAME
                    ]);
                $dataProduct['image'] = $uploadResult['secure_url'];
            } else {
                return response()->json([
                    'message' => 'File không đúng định dạng'
                ], Response::HTTP_BAD_REQUEST);
            }

            $product = Product::query()->create($dataProduct);

            if (!empty($dataVariant)) {
                foreach ($dataVariant as $variant) {
                    $uploadedImages = [];
                    if (!empty($variant['image'])) {
                        foreach ($variant['image'] as $image) {
                            if ($image->isValid()) {
                                $uploadResult = $this->cloudinary
                                    ->uploadApi()
                                    ->upload($image->getRealPath(), [
                                        'folder' => self::FOLODER_NAME_VARIANT
                                    ]);
                                $uploadedImages[] = $uploadResult['secure_url'];
                            }
                        }
                    }

                    $variant['image'] = json_encode($uploadedImages);

                    $variantModel = $product->variants()->create($variant);

                    if (!empty($variant['attribute_values'])) {
                        foreach ($variant['attribute_values'] as $attributeValue) {
                            $variantModel->attributeValues()->attach($attributeValue);
                        }
                    }
                }
            }

            DB::commit();

            return response()->json([
                'message' => 'Thao tác thành công',
                'data' => $product
            ], Response::HTTP_CREATED);
        } catch (\Exception $e) {
            DB::rollBack();

            if (!empty($uploadResult['secure_url'])) {
                $publicId = pathinfo(parse_url($uploadResult['secure_url'], PHP_URL_PATH), PATHINFO_FILENAME);
                $publicIdWithFolder = self::FOLDER_NAME . '/' . $publicId;
                $this->cloudinary
                    ->uploadApi()
                    ->destroy($publicIdWithFolder);
            }

            Log::error(__CLASS__ . '@' . __FUNCTION__, [
                'message' => $e->getMessage(),
                'line' => $e->getLine(),
            ]);

            return response()->json([
                'message' => 'Lỗi server',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        try {
            $data = Product::query()
                ->with(['category', 'variants'])
                ->find($id);

            if (!$data) {
                return response()->json([
                    'message' => 'Không có dữ liệu'
                ], Response::HTTP_NOT_FOUND);
            }

            $data->variants->map(function ($variant) {
                $variant->image = json_decode($variant->image);
                $variant->attribute_values = $variant->attributeValues->pluck('id')->toArray();
            });

            return response()->json([
                'message' => 'Chi tiết sản phẩm: ' . $data->name,
                'data' => $data
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            Log::error(__CLASS__ . '@' . __FUNCTION__, [
                'message' => $e->getMessage(),
                'line' => $e->getLine(),
            ]);

            return response()->json([
                'message' => 'Lỗi server',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateProductRequest $request, string $id)
    {
        try {
            $product = Product::query()->find($id);
            if (!$product) {
                return response()->json([
                    'message' => 'Không có dữ liệu'
                ], Response::HTTP_NOT_FOUND);
            }

            $dataProduct = $request->except('variants', 'image');
            $dataVariant = $request->variants;
            $dataProduct['slug'] = $dataProduct['name'] ? Str::slug($dataProduct['name'], '-') : $product->slug;

            $image = $request->file('image') ?? null;

            if ($image->isValid()) {
                $uploadResult = $this->cloudinary
                    ->uploadApi()
                    ->upload($image->getRealPath(), [
                        'folder' => self::FOLDER_NAME
                    ]);
                $dataProduct['image'] = $uploadResult['secure_url'];
            } else {
                return response()->json([
                    'message' => 'File không đúng định dạng'
                ], Response::HTTP_BAD_REQUEST);
            }

            $product = Product::query()->ceate($dataProduct);

            if (!empty($dataVariant)) {
                foreach ($dataVariant as $variant) {
                    $uploadedImages = [];
                    if (!empty($variant['image'])) {
                        foreach ($variant['image'] as $image) {
                            if ($image->isValid()) {
                                $uploadResult = $this->cloudinary
                                    ->uploadApi()
                                    ->upload($image->getRealPath(), [
                                        'folder' => self::FOLODER_NAME_VARIANT
                                    ]);
                                $uploadedImages[] = $uploadResult['secure_url'];
                            }
                        }
                    }

                    $variant['image'] = json_encode($uploadedImages);

                    $variantModel = $product->variants()->create($variant);

                    if (!empty($variant['attribute_values'])) {
                        foreach ($variant['attribute_values'] as $attributeValue) {
                            $variantModel->attributeValues()->attach($attributeValue);
                        }
                    }
                }
            }

            DB::commit();

            return response()->json([
                'message' => 'Thao tác thành công',
                'data' => $product
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
            $product = Product::query()->find($id);

            if (!$product) {
                return response()->json([
                    'message' => 'Không có dữ liệu'
                ], Response::HTTP_NOT_FOUND);
            }

            $product->delete();

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

    private function processProductData($request)
    {
        $dataProduct = $request->except('variants', 'image');
        $dataProduct['slug'] = $dataProduct['name'] ? Str::slug($dataProduct['name'], '-') : null;

        if ($request->hasFile('image')) {
            $image = $request->file('image');
            if ($image->isValid()) {
                $uploadResult = $this->cloudinary
                    ->uploadApi()
                    ->upload($image->getRealPath(), [
                        'folder' => self::FOLDER_NAME
                    ]);
                $dataProduct['image'] = $uploadResult['secure_url'];
            } else {
                return response()->json([
                    'message' => 'File không đúng định dangjF'
                ], Response::HTTP_BAD_REQUEST);
            }
        }
        return $dataProduct;
    }

    private function processProductVariants($dataVariant, $product)
    {
        foreach ($dataVariant as $variant) {
            $uploadedImages = [];
            if (!empty($variant['image'])) {
                foreach ($variant['image'] as $image) {
                    if ($image->isValid()) {
                        $uploadResult = $this->cloudinary
                            ->uploadApi()
                            ->upload($image->getRealPath(), [
                                'folder' => self::FOLODER_NAME_VARIANT
                            ]);
                        $uploadedImages[] = $uploadResult['secure_url'];
                    }
                }
            }

            $variant['image'] = json_encode($uploadedImages);

            $variantModel = $product->variants()->create($variant);

            if (!empty($variant['attribute_values'])) {
                foreach ($variant['attribute_values'] as $attributeValue) {
                    $variantModel->attributeValues()->attach($attributeValue);
                }
            }
        }
    }

}
