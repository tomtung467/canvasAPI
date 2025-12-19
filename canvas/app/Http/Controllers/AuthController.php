<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

use App\Services\User\UserService;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller
{
     protected $userService;
    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
        $this->middleware('auth:api', ['except' => ['login','refresh']]);
    }
            public function login()
    {
                request()->validate([
                        'email' => 'required|email',
                        'password' => 'required',
                ]);
                $credentials = request(['email', 'password']);

        if (! $token = auth()->attempt($credentials)) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $refreshToken = $this->createRefreshToken();
        return $this->respondWithToken($token, $refreshToken);
    }
     private function respondWithToken($token, $refreshToken)
    {
        return response()->json([
            'access_token' => $token,
            'refresh_token' => $refreshToken,
            'token_type' => 'bearer',
            'expires_in' =>config('jwt.ttl') * 60
        ]);
    }
    public function profile()
    {
        try {
            return response()->json(auth()->user());
        } catch (JWTException $e) {
            return response()->json(['error' => 'Could not retrieve user profile'], 401);
        }
    }
    public function logout()
    {
        auth()->logout();

        return response()->json(['message' => 'Successfully logged out']);
    }
    public function refresh()
    {
        $refreshToken = request('refresh_token');
        if (! $refreshToken) {
            return response()->json(['error' => 'refresh_token is required'], 400);
        }
        try {
             $decoded = JWTAuth::manager()->getJWTProvider()->decode($refreshToken);
             try {
             $user = User::find($decoded['subac']);
             } catch (\Exception $e) {
                 return response()->json(['error' => 'this is not the refresh_token'], 404);
             }
             if (! $user) {
                 return response()->json(['error' => 'User not found'], 404);
             }
             $token = auth()->login($user);
             $refreshToken = $this->createRefreshToken();
        return $this->respondWithToken($token, $refreshToken);
        } catch(JWTException $exception)
        {
            return response()->json(['error' => 'Invalid refresh token'], 401);
        }
    }
    private function createRefreshToken()
    {
        $refreshToken = JWTAuth::manager()->getJWTProvider()->encode([
            'subac'    => auth()->user()->id,
            'random' => (string) (rand() . time()),
            'exp'    => time() + config('jwt.refresh_ttl') * 60,
        ]);
        return $refreshToken;
    }

}
