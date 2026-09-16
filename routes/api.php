<?php

use App\Http\Controllers\Api\OneC\V1\OneCController;
use App\Http\Controllers\Api\Cart\CartController;
use App\Http\Controllers\Api\Cart\CheckoutController;
use App\Http\Controllers\Api\Account\AccountDocumentsController;
use App\Http\Controllers\Api\Account\CustomerProfileController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::prefix('1c/v1')
    ->middleware('onec.auth')
    ->group(function (): void {
        Route::get('/ping', [OneCController::class, 'ping']);

        Route::post('/categories/batch', [OneCController::class, 'categories']);
        Route::post('/products/batch', [OneCController::class, 'products']);
        Route::post('/prices/batch', [OneCController::class, 'prices']);
        Route::post('/stocks/batch', [OneCController::class, 'stocks']);

        Route::get('/orders', [OneCController::class, 'orders']);
        Route::post('/orders/{order:uuid}/status', [OneCController::class, 'orderStatus']);
    });

Route::middleware('auth:web')->prefix('api/cart')->name('cart.api.')->group(function (): void {
    Route::get('/', [CartController::class, 'show'])->name('show');
    Route::post('/items', [CartController::class, 'add'])->name('items.add');
    Route::patch('/items/{item}', [CartController::class, 'update'])->name('items.update');
    Route::delete('/items/{item}', [CartController::class, 'destroy'])->name('items.destroy');
    Route::post('/checkout', CheckoutController::class)->name('checkout');
});

Route::middleware('auth:web')->prefix('account/api')->name('account.api.')->group(function (): void {
    Route::get('/profile', [CustomerProfileController::class, 'show'])->name('profile');
    Route::put('/profile', [CustomerProfileController::class, 'update'])->name('profile.update');
    Route::get('/documents', [AccountDocumentsController::class, 'documents'])->name('documents');
    Route::post('/orders/{order}/invoice', [AccountDocumentsController::class, 'requestInvoice'])->name('orders.invoice');
});
