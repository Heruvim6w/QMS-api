<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\User\LoginRequest;
use App\Http\Requests\User\RegisterRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpFoundation\Response as ResponseAlias;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller
{
    public function register(RegisterRequest $request): JsonResponse
    {
        $data = $request->validated();

        $user = User::create([
            'name' => $data->name,
            'email' => $data->email,
            'password' => Hash::make($data->password),
        ]);

        $user->generateKeyPair();
        $user->save();

        $token = JWTAuth::fromUser($user);

        return response()->json(
            [
                'user' => $user,
                'access_token' => $token,
                'token_type' => 'bearer',
                'expires_in' => config('jwt.ttl') * 60
            ],
            ResponseAlias::HTTP_CREATED
        );
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $data = $request->validated();

        $credentials = $data;

        if (!$token = JWTAuth::attempt($credentials)) {
            return response()->json(
                ['error' => 'Unauthorized'],
                ResponseAlias::HTTP_UNAUTHORIZED
            );
        }

        return $this->respondWithToken($token);
    }

    public function logout(): JsonResponse
    {
        JWTAuth::invalidate(JWTAuth::getToken());

        return response()->json(['message' => 'Successfully logged out']);
    }

    public function refresh()
    {
        return $this->respondWithToken(JWTAuth::refresh(JWTAuth::getToken()));
    }

    public function me(): JsonResponse
    {
        return response()->json(auth()->user());
    }

    protected function respondWithToken(string $token): JsonResponse
    {
        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => config('jwt.ttl') * 60,
            'user' => auth()->user()
        ]);
    }
}
