<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\SocialLoginController;
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
