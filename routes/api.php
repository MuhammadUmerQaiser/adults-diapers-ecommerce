<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CartController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\SocialLoginController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\GlobalController;

/* |-------------------------------------------------------------------------- | API Routes |-------------------------------------------------------------------------- | | Here is where you can register API routes for your application. These | routes are loaded by the RouteServiceProvider and all of them will | be assigned to the "api" middleware group. Make something great! | */

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::controller(GlobalController::class)->group(function () {
    Route::get('/get-sizes', 'getAllSizes')->name('get-sizes');
    Route::get('/get-categories', 'getAllCategories')->name('get-categories');
    Route::get('/get-roles', 'getAllRoles')->name('get-roles');
});

Route::controller(AuthController::class)->group(function () {
    Route::post('/register', 'register')->name('register');
    Route::post('/login', 'login')->name('login');
    Route::post('/forgot-password', 'forgotPassword')->name('forgot-password');
    Route::post('/verify-code', 'verifyCode')->name('verify-code');
    Route::post('/reset-password', 'resetPassword')->name('reset-password');
});

Route::controller(SocialLoginController::class)->group(function () {
    Route::get('/social/{provider}/redirect', 'redirectToProviderPlatform')->name('auth.social.redirect');
    Route::get('/social/{provider}/callback', 'handleProviderCallback')->name('auth.social.callback');
});

Route::controller(ProductController::class)->group(function () {
    Route::get('/products', 'index')->name('products.index');
    Route::get('/products/{slug}', 'show')->name('products.show');
});

Route::controller(CartController::class)->prefix('cart')->group(function () {
    Route::get('/', 'index')->name('cart.index');        // Get cart items
    Route::get('/quantity', 'quantity')->name('cart.quantity');  // Navbar badge count
    Route::post('/', 'addOrUpdate')->name('cart.add');    // Add / +1 / -1
    Route::delete('/item', 'remove')->name('cart.remove');      // Force remove item
    Route::delete('/', 'clear')->name('cart.clear');        // Clear entire cart
});


//AUTH USER ROUTES
Route::middleware('auth:api')->group(function () {
    Route::controller(CartController::class)->prefix('cart')->group(function () {
        Route::post('/merge', 'merge')->name('cart.merge'); // guest → user cart merge
    });
});

//ADMIN ROUTES

Route::middleware(['auth:api', 'admin'])->group(function () {
    Route::controller(ProductController::class)->group(function () {
        Route::post('/products', 'store')->name('products.store');
        Route::post('/products/{id}', 'update')->name('products.update');
        Route::delete('/products/{id}', 'destroy')->name('products.destroy');
    });
});