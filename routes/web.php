<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\CatalogController;
use App\Http\Controllers\Auth\CustomerLoginController;
use App\Http\Controllers\Auth\PhoneLoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\EmailLoginController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\AccountController;

Route::middleware('guest:web')->group(function (): void {
    Route::get('/login', [CustomerLoginController::class, 'create'])->name('login');
    Route::post('/login/email', EmailLoginController::class)->name('login.email');
    Route::post('/login/phone/send', [PhoneLoginController::class, 'send'])
        ->middleware('throttle:5,1')
        ->name('login.phone.send');
    Route::get('/login/phone/verify', [PhoneLoginController::class, 'verifyForm'])->name('login.phone.verify');
    Route::post('/login/phone/verify', [PhoneLoginController::class, 'verify'])
        ->middleware('throttle:10,1')
        ->name('login.phone.verify.submit');

    Route::get('/register', [RegisterController::class, 'create'])->name('register');
    Route::post('/register/email', [RegisterController::class, 'email'])->name('register.email');
    Route::post('/register/phone/send', [RegisterController::class, 'phoneSend'])->name('register.phone.send');
    Route::get('/register/phone/verify', [RegisterController::class, 'phoneVerifyForm'])->name('register.phone.verify.form');
    Route::post('/register/phone/verify', [RegisterController::class, 'phoneVerify'])->name('register.phone.verify');

    Route::get('/forgot-password', [PasswordResetLinkController::class, 'create'])->name('password.request');
    Route::post('/forgot-password', [PasswordResetLinkController::class, 'store'])->name('password.email');
    Route::get('/reset-password/{token}', [NewPasswordController::class, 'create'])->name('password.reset');
    Route::post('/reset-password', [NewPasswordController::class, 'store'])->name('password.update');
});

Route::middleware('auth:web')
    ->prefix('account')
    ->name('account.')
    ->group(function (): void {
        Route::get('/', [AccountController::class, 'show'])
            ->name('index');

        Route::patch('/profile', [AccountController::class, 'updateProfile'])
            ->name('profile.update');

        Route::post('/phone/send', [AccountController::class, 'sendPhoneCode'])
            ->middleware('throttle:5,1')
            ->name('phone.send');

        Route::post('/phone/verify', [AccountController::class, 'verifyPhone'])
            ->middleware('throttle:10,1')
            ->name('phone.verify');

        Route::post('/email/send', [AccountController::class, 'sendEmailVerification'])
            ->middleware('throttle:5,1')
            ->name('email.send');

        Route::get('/email/verify/{token}', [AccountController::class, 'verifyEmail'])
            ->middleware('signed')
            ->name('email.verify');

        Route::put('/password', [AccountController::class, 'updatePassword'])
            ->name('password.update');
    });

Route::post('/logout', [CustomerLoginController::class, 'destroy'])
    ->middleware('auth:web')
    ->name('logout');

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
