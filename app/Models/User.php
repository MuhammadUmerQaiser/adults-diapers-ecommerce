<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Http\Resources\UserResource;
use App\Notifications\ForgotPasswordNotification;
use App\Notifications\UserRegistrationNotification;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Http\Request;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\URL;
use Laravel\Passport\HasApiTokens;
use Laravel\Socialite\Socialite;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    // protected $fillable = [
    //     'firstname',
    //     'lastname',
    //     'email',
    //     'password',
    // ];
    protected $guarded = ["id"];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'dob' => 'date',
        'password' => 'hashed',
    ];

    public function getUserByEmail($email)
    {
        return $this->where('email', $email)->first();
    }

    public function getUserById($id)
    {
        return $this->findOrFail($id);
    }


    public function addUser(Request $request)
    {
        DB::beginTransaction();
        try {
            $userImage = null;
            if ($request->hasFile('image')) {
                $userImage = saveFile($request->image, 'images/users', $request->image->getClientOriginalName());
            }
            $user = User::create([
                'firstname' => $request->firstname,
                'lastname' => $request->lastname,
                'username' => $request->username,
                'email' => $request->email,
                'image' => $userImage['name'] ?? null,
                'dob' => $request->dob,
                'phone' => $request->phone,
                'gender' => $request->gender,
                'password' => Hash::make($request->password),
            ]);
            $verificationUrl = URL::signedRoute(
                'verify.user.email',
                ['user_id' => $user->id]
            );

            $user->notify(new UserRegistrationNotification($user, $verificationUrl));
            DB::commit();
            return api_success(new UserResource($user), 'User registered successfully', 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return api_error('Something went wrong while creating the user.', 500, $e->getMessage());
        }
    }

    public function checkUserLogin(Request $request)
    {
        try {
            $user = $this->getUserByEmail($request->email);
            if (!$user || !Hash::check($request->password, $user->password)) {
                return api_error('The provided credentials are incorrect.', 401);
            } else if (!$user->email_verified_at) {
                return api_error('Email is not verified.', 403);
            } else if ($user->social_id && $user->social_type) {
                return api_error('This email is registered via social login. Please use social login to access your account.', 403);
            } else if (Auth::attempt($request->all())) {
                $checkedUser = Auth::getLastAttempted();
                Auth::login($checkedUser);
                $token = Auth::user()->createToken('LaravelAuthApp')->accessToken;
                $data = [
                    'user' => new UserResource($user),
                    'token' => $token,
                ];
                return api_success($data, 'User logged in successfully', 200);
            }
            return api_error('The provided credentials are incorrect.', 401);
        } catch (\Exception $e) {
            return api_error('Something went wrong while logging in.', 500, $e->getMessage());
        }
    }

    public function verifyUserEmail(Request $request, $userId)
    {
        try {
            if (!$request->hasValidSignature()) {
                return api_error('Invalid or expired verification link.', 403);
            }

            $user = $this->getUserById($userId);
            if (!$user) {
                return api_error('User not found.', 404);
            }

            if ($user->email_verified_at) {
                return api_success(null, 'Email is already verified.', 200);
            }

            $user->email_verified_at = now();
            $user->save();

            //right now return as response, later redirect to frontend url

            return api_success(null, 'Email verified successfully.', 200);
        } catch (\Exception $e) {
            return api_error('Something went wrong while verifying user.', 500, $e->getMessage());
        }
    }

    // public function verifyUserEmail(Request $request, $userId)
    // {
    //     $redirectBase = config('app.frontend_verify_url');
    //     // Example: https://myapp.com/verify-email-result

    //     // 1. Invalid Signature
    //     if (!$request->hasValidSignature()) {
    //         $url = $redirectBase . '?status=error&message=' . urlencode('Invalid or expired verification link.');
    //         return redirect()->away($url);
    //     }

    //     $user = $this->getUserById($userId);
    //     if (!$user) {
    //         $url = $redirectBase . '?status=error&message=' . urlencode('User not found.');
    //         return redirect()->away($url);
    //     }

    //     if ($user->email_verified_at) {
    //         $url = $redirectBase . '?status=success&message=' . urlencode('Email is already verified.');
    //         return redirect()->away($url);
    //     }

    //     $user->email_verified_at = now();
    //     $user->save();

    //     $url = $redirectBase . '?status=success&message=' . urlencode('Email verified successfully.');
    //     return redirect()->away($url);
    // }

    public function forgotPassword(Request $request)
    {
        DB::beginTransaction();
        try {
            $randomToken = str_pad(rand(0, 9999), 4, '0', STR_PAD_LEFT);
            $token = generateUniqueCode($randomToken, PasswordReset::class);
            $user = $this->getUserByEmail($request->email);
            $passwordReset = PasswordReset::updateOrCreate(
                [
                    'email' => $request->email,
                ],
                [
                    'token' => $token,
                    'created_at' => now()
                ]
            );
            $user->notify(new ForgotPasswordNotification($user, $token));
            DB::commit();
            return api_success(null, 'Code has been sent to your email, please verify.', 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return api_error('Something went wrong.', 500, $e->getMessage());
        }
    }

    public function resetPassword(Request $request)
    {
        try {
            $passwordReset = PasswordReset::where('token', $request->token)->first();
            if ($passwordReset) {
                $user = $this->getUserByEmail($passwordReset->email);
                $user->password = Hash::make($request->password);
                $user->update();

                $passwordReset->delete();

                return api_success(new UserResource($user), 'Your password has been reset.', 200);
            } else {
                return api_error('Code is invalid.', 401);
            }
        } catch (\Exception $e) {
            return api_error('Something went wrong while resetting password.', 500, $e->getMessage());
        }

    }

    public function redirectToProviderPlatform($provider)
    {
        try {
            $url = Socialite::driver($provider)->stateless()->redirect()->getTargetUrl();
            return api_success(['url' => $url], 'Redirect URL generated successfully.', 200);
        } catch (\Exception $e) {
            return api_error('Something went wrong while redirecting to social platform.', 500, $e->getMessage());
        }
    }

    public function handleProviderCallback($provider)
    {
        DB::beginTransaction();
        try {
            $user = Socialite::driver($provider)->stateless()->user();
            $authUser = User::updateOrCreate(
                ['social_id' => $user->id],
                [
                    'firstname' => $user->user['given_name'] ?? '',
                    'lastname' => $user->user['family_name'] ?? '',
                    'email' => $user->email,
                    'email_verified_at' => now(),
                    'password' => Hash::make(uniqid()),
                    'social_type' => $provider,
                ]
            );
            Auth::login($authUser);
            $token = $authUser->createToken('LaravelAuthApp')->accessToken;
            $data = [
                'user' => new UserResource($authUser),
                'token' => $token,
            ];
            DB::commit();
            return api_success($data, 'User logged in successfully', 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return api_error('Something went wrong while handling social callback.', 500, $e->getMessage());
        }
    }


}
