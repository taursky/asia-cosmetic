<?php

use App\Http\Controllers\Api\OneC\V1\OneCController;
use App\Http\Controllers\Api\Cart\CartController;
use App\Http\Controllers\Api\Cart\CheckoutController;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::prefix('1c/v1')
    ->middleware('onec.auth')
    ->group(function (): void {
        Route::get('/ping', [OneCController::class, 'ping']);

        Route::post('/warehouses/batch', [OneCController::class, 'warehouses']);
        Route::post('/categories/batch', [OneCController::class, 'categories']);
        Route::post('/products/batch', [OneCController::class, 'products']);
        Route::post('/price-types/batch', [OneCController::class, 'priceTypes']);
        Route::post('/prices/batch', [OneCController::class, 'prices']);
        Route::post('/stocks/batch', [OneCController::class, 'stocks']);
        Route::post('/images', [OneCController::class, 'image']);

        Route::get('/orders', [OneCController::class, 'orders']);
        Route::post('/orders/{order:uuid}/status', [OneCController::class, 'orderStatus']);
    });
