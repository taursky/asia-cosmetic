<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\CatalogController;

//Route::get('/', function () {
//    return view('home');
//});
Route::get('/', HomeController::class)->name('home');

Route::prefix('catalog')->name('catalog.')->group(function (): void {
    Route::get('/', [CatalogController::class, 'index'])->name('index');
    Route::get('/{categorySlug}', [CatalogController::class, 'category'])
        ->where('categorySlug', '[A-Za-z0-9\-_]+')
        ->name('category');
});

Route::get('/product/{productSlug}', [CatalogController::class, 'product'])
    ->where('productSlug', '[A-Za-z0-9\-_]+')
    ->name('catalog.product');
