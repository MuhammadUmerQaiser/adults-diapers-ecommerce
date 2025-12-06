<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class SocialLoginController extends Controller
{
    protected $user;

    public function __construct(User $user)
    {
        $this->user = $user;
    }

    public function redirectToProviderPlatform(Request $request, $provider)
    {
        return $this->user->redirectToProviderPlatform($provider);
    }

    public function handleProviderCallback(Request $request, $provider)
    {
        return $this->user->handleProviderCallback($provider);
    }
}
