<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// API AUTH
Route::post('auth/login', [\App\Http\Controllers\API\Auth\AuthController::class, 'login']);
Route::post('auth/register', [\App\Http\Controllers\API\Auth\AuthController::class, 'register']);

Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('auth/user', [\App\Http\Controllers\API\Auth\AuthController::class, 'user']);

    Route::post('auth/logout', [\App\Http\Controllers\API\Auth\AuthController::class, 'logout']);

    // API SEARCH
    Route::get('dashboard/search', [\App\Http\Controllers\API\DashboardController::class, 'search']);

    // API CATEGORIES
    Route::apiResource('categories', \App\Http\Controllers\API\CategoryController::class);

    // API ATTRIBUTES
    Route::apiResource('attributes', \App\Http\Controllers\API\AttributeController::class);

    // API ATTRIBUTES VALUE
    Route::apiResource('attribute-values', \App\Http\Controllers\API\AttributeValueController::class);

    // API PRODUCTS
    Route::apiResource('products', \App\Http\Controllers\API\ProductController::class);
    Route::get('products/restore/{product}', [\App\Http\Controllers\API\ProductController::class, 'restore']);

    // API CART
    Route::prefix('carts')->group(function () {
        Route::get('/', [\App\Http\Controllers\API\CartController::class, 'index']);
        Route::post('/', [\App\Http\Controllers\API\CartController::class, 'store'])
            ->name('carts.store');

        Route::put('/increase', [\App\Http\Controllers\API\CartController::class, 'increaseQuantity'])
            ->name('carts.increase');

        Route::put('/decrease', [\App\Http\Controllers\API\CartController::class, 'decreaseQuantity'])
            ->name('carts.decrease');

        Route::delete('/destroy/{id}', [\App\Http\Controllers\API\CartController::class, 'destroy'])
            ->name('carts.destroy');

        Route::delete('/clear-cart', [\App\Http\Controllers\API\CartController::class, 'clearCart'])
            ->name('carts.clear');
    });

    // API COUPON
    Route::apiResource('coupons', \App\Http\Controllers\API\CouponController::class);

    // API USERS
    Route::apiResource('users', \App\Http\Controllers\API\UserController::class);
    Route::get('users/restore/{user}', [\App\Http\Controllers\API\UserController::class, 'restore']);
    Route::delete('users/{user}/force-destroy', [\App\Http\Controllers\API\UserController::class, 'forceDestroy']);

});




