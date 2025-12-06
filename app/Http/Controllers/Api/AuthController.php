<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Http\Requests\Auth\VerifyCodeRequest;
use App\Models\User;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    protected $user;

    public function __construct(User $user)
    {
        $this->user = $user;
    }

    public function register(RegisterRequest $request)
    {
        return $this->user->addUser($request);
    }

    public function login(LoginRequest $request)
    {
        return $this->user->checkUserLogin($request);
    }

    public function verifyUserEmail(Request $request, $userId)
    {
        return $this->user->verifyUserEmail($request, $userId);
    }

    public function forgotPassword(ForgotPasswordRequest $request)
    {
        return $this->user->forgotPassword($request);

    }

    public function verifyCode(VerifyCodeRequest $request)
    {
        return api_success(null, 'Code has been verified.', 200);
    }

    public function resetPassword(ResetPasswordRequest $request)
    {
        return $this->user->resetPassword($request);
    }

}
