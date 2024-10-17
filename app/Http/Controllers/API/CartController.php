<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\API\StoreCartRequest;
use App\Http\Resources\CartItemResource;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\Variant;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CartController extends Controller
{
    /**
     * [GET]: /api/carts - Danh sách sản phẩm trong giỏ hàng
     */
    public function index()
    {
        try {
            if (Auth::check()) {
                $userId = Auth::id();
                $cart = Cart::query()
                    ->where('user_id', $userId)
                    ->first();

                if (!$cart) {
                    return response()->json([
                        'message' => 'Không tìm thấy giỏ hàng'
                    ], Response::HTTP_NOT_FOUND);
                }

                $cartItem = $cart->cartItems()->with(['variant.product', 'variant.attributeValues', 'product'])->get();
                $cartItem2 = CartItemResource::collection($cart->cartItems()->with(['variant.product', 'variant.attributeValues', 'product'])->get());
                return response()->json([
                    'message' => 'Giỏ hàng của người dùng: ' . $userId,
                    'data' => $cartItem2
                ], Response::HTTP_OK);
            } else {
                return response()->json([
                    'message' => 'Vui lòng đăng nhapajF'
                ], Response::HTTP_NOT_FOUND);
            }
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
     * [POST]: /api/carts - Thêm sản phẩm vào giỏ hàng
     */
    public function store(StoreCartRequest $request)
    {
        try {
            DB::beginTransaction();
            $data = $request->validated();
            $product = Product::query()->find($data['product_id']);
            $productVariant = Variant::query()
                ->with('attributeValues')
                ->where([
                    'product_id' => $product->id
                ])
                ->first();

            if (empty($product)) {
                return response()->json([
                    'message' => 'Không có dữ liệu'
                ], Response::HTTP_NOT_FOUND);
            }

            $cart = session('cart', []);

            if ($productVariant) {
                $variantId = $productVariant->id;
                if (isset($cart[$variantId])) {
                    $cart[$variantId]['quantity'] += $data['qty'];
                } else {
                    $cart[$variantId] = [
                        'product_id' => $product->id,
                        'variant_id' => $variantId,
                        'quantity' => $data['qty']
                    ];
                }
            } else {
                if (isset($cart[$product->id])) {
                    $cart[$product->id]['quantity'] += $data['qty'];
                } else {
                    $cart[$product->id] = [
                        'product_id' => $product->id,
                        'quantity' => $data['qty']
                    ];
                }
            }

            session()->put('cart', $cart);

            if (Auth::check()) {
                $userId = Auth::id();
                $userCart = Cart::query()->firstOrCreate(['user_id' => $userId]);

                foreach ($cart as $item) {
                    if (isset($item['variant_id'])) {
                        $cartItem = CartItem::query()
                            ->where([
                                'cart_id' => $userCart->id,
                                'product_id' => $item['product_id'],
                                'variant_id' => $item['variant_id']
                            ])
                            ->first();

                        if ($cartItem) {
                            $cartItem->quantity += $item['quantity'];
                            $cartItem->save();
                        } else {
                            CartItem::query()->create([
                                'user_id' => $userCart,
                                'product_id' => $item['product_id'],
                                'variant_id' => $item['variant_id'],
                                'quantity' => $item['quantity']
                            ]);
                        }
                    } else {
                        $cartItem = CartItem::query()
                            ->where([
                                'cart_id' => $userCart->id,
                                'product_id' => $item['product_id']
                            ])
                            ->first();

                        if ($cartItem) {
                            $cartItem->quantity += $item['quantity'];
                            $cartItem->save();
                        } else {
                            CartItem::query()->create([
                                'user_id' => $userCart,
                                'product_id' => $item['product_id'],
                                'quantity' => $item['quantity']
                            ]);
                        }
                    }
                }
            }

            DB::commit();

            return response()->json([
                'message' => 'Thao tác thành công',
                'data' => $cart
            ], Response::HTTP_CREATED);
        } catch (\Exception $e) {
            DB::rollBack();

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
     * [PUT]: /api/carts/increase/{id} - Tăng số lượng sản phẩm
     */
    public function increaseQuantity(Request $request)
    {
        try {
            DB::beginTransaction();

            $data = $request->validate([
                'product_id' => 'required|exists:products,id',
                'variant_id' => 'sometimes|nullable|exists:variants,id',
                'qty' => 'required|numeric|min:1'
            ]);

            if (Auth::check()) {
                $userId = Auth::id();
                $cart = Cart::query()
                    ->where('user_id', $userId)
                    ->first();

                if (!$cart) {
                    return response()->json([
                        'message' => 'Không tìm thấy giỏ hàng'
                    ], Response::HTTP_NOT_FOUND);
                }

                if (isset($data['variant_id'])) {
                    $cartItem = CartItem::query()
                        ->where([
                            'cart_id' => $cart->id,
                            'product_id' => $data['product_id'],
                            'variant_id' => $data['variant_id']
                        ])
                        ->first();
                } else {
                    $cartItem = CartItem::query()
                        ->where([
                            'cart_id' => $cart->id,
                            'product_id' => $data['product_id']
                        ])
                        ->first();
                }

                if (!$cartItem) {
                    return response()->json([
                        'message' => 'Sản phẩm khng có trong giỏ hàng'
                    ], Response::HTTP_NOT_FOUND);
                }

                $cartItem->quantity += $data['qty'];
                $cartItem->save();

                DB::commit();

                return response()->json([
                    'message' => 'Thao tác thành công',
                    'data' => new CartItemResource($cartItem)
                ], Response::HTTP_OK);
            } else {
                return response()->json([
                    'message' => 'Vui lòng đăng nhập'
                ], Response::HTTP_UNAUTHORIZED);
            }
        } catch (\Exception $e) {
            DB::rollBack();

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
     * [PUT]: /api/carts/decrease/{id} - Giảm số lượng sản phẩm
     */
    public function decreaseQuantity(Request $request)
    {
        try {
            DB::beginTransaction();

            $data = $request->validate([
                'product_id' => 'required|exists:products,id',
                'variant_id' => 'sometimes|nullable|exists:variants,id',
                'qty' => 'required|numeric|min:1'
            ]);

            if (Auth::check()) {
                $userId = Auth::id();
                $cart = Cart::query()
                    ->where('user_id', $userId)
                    ->first();

                if (!$cart) {
                    return response()->json([
                        'message' => 'Không tìm thấy giỏ hàng'
                    ], Response::HTTP_NOT_FOUND);
                }

                if (isset($data['variant_id'])) {
                    $cartItem = CartItem::query()
                        ->where([
                            'cart_id' => $cart->id,
                            'product_id' => $data['product_id'],
                            'variant_id' => $data['variant_id']
                        ])
                        ->first();
                } else {
                    $cartItem = CartItem::query()
                        ->where([
                            'cart_id' => $cart->id,
                            'product_id' => $data['product_id']
                        ])
                        ->first();
                }

                if (!$cartItem) {
                    return response()->json([
                        'message' => 'Sản phẩm khng có trong giỏ hàng'
                    ], Response::HTTP_NOT_FOUND);
                }

                if ($cartItem->quantity > 1) {
                    $cartItem->quantity -= $data['qty'];
                    $cartItem->save();
                } else {
                    return response()->json([
                        'message' => 'Số lượng sản phẩm tổi thiểu là 1'
                    ], Response::HTTP_UNPROCESSABLE_ENTITY);
                }

                DB::commit();

                return response()->json([
                    'message' => 'Thao tác thành công',
                    'data' => new CartItemResource($cartItem)
                ], Response::HTTP_OK);
            } else {
                return response()->json([
                    'message' => 'Vui lòng đăng nhập'
                ], Response::HTTP_UNAUTHORIZED);
            }
        } catch (\Exception $e) {
            DB::rollBack();

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
     * [DELETE]: /api/carts/destroy/{id} - Xóa sản phẩm trong giờ hãng
     */
    public function destroy(string $id)
    {
        try {
            if (Auth::check()) {
                $userId = Auth::id();
                $cart = Cart::query()
                    ->where('user_id', $userId)
                    ->first();

                if (!empty($cart)) {
                    $cartItem = CartItem::query()
                        ->where([
                            'cart_id' => $cart->id,
                            'product_id' => $id
                        ])
                        ->first();

                    if (!empty($cartItem)) {
                        $cartItem->delete();
                        return response()->json([
                            'message' => 'Xoá sản phẩm khỏi giỏ hàng thành công'
                        ], Response::HTTP_OK);
                    } else {
                        return response()->json([
                            'message' => 'Sản phẩm không có trong giỏ hàng'
                        ], Response::HTTP_NOT_FOUND);
                    }
                } else {
                    return response()->json([
                        'message' => 'Không tìm thấy giỏ hàng'
                    ], Response::HTTP_NOT_FOUND);
                }
            }
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
     * [DELETE]: /api/carts/clear-all - Xoá toàn bộ sản phẩm trong giỏ hàng
     */
    public function clearCart()
    {
        try {
            if (Auth::check()) {
                $userId = Auth::id();
                $cart = Cart::query()
                    ->where('user_id', $userId)
                    ->first();

                if (!empty($cart)) {
                    $cart->cartItems()->delete();
                    return response()->json([
                        'message' => 'Xoá giỏ hàng thành công'
                    ], Response::HTTP_OK);
                } else {
                    return response()->json([
                        'message' => 'Không tìm thấy giỏ hàng'
                    ], Response::HTTP_NOT_FOUND);
                }
            }
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
