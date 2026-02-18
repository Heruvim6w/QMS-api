<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\User\ConfirmLoginRequest;
use App\Http\Requests\User\LoginRequest;
use App\Http\Requests\User\RegisterRequest;
use App\Models\User;
use App\Services\LoginService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use OpenApi\Annotations as OA;
use Symfony\Component\HttpFoundation\Response;
use Tymon\JWTAuth\Facades\JWTAuth;

/**
 * @OA\Tag(
 *     name="Authentication",
 *     description="API Endpoints for User Authentication"
 * )
 */
class AuthController extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/v1/register",
     *     summary="Register a new user",
     *     tags={"Authentication"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name", "email", "password", "password_confirmation"},
     *             @OA\Property(property="name", type="string", example="John Doe"),
     *             @OA\Property(property="email", type="string", format="email", example="john@example.com"),
     *             @OA\Property(property="password", type="string", format="password", example="password"),
     *             @OA\Property(property="password_confirmation", type="string", format="password", example="password")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="User registered successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="user", ref="#/components/schemas/User"),
     *             @OA\Property(property="access_token", type="string", example="eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9..."),
     *             @OA\Property(property="token_type", type="string", example="bearer"),
     *             @OA\Property(property="expires_in", type="integer", example=3600)
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="The given data was invalid."),
     *             @OA\Property(property="errors", type="object", example={"email": {"The email has already been taken."}})
     *         )
     *     )
     * )
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $data = $request->validated();

        $user = User::query()
            ->create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
            ])
        ;

        $user->generateKeyPair();
        $user->save();

        $token = JWTAuth::fromUser($user);

        return response()->json(
            [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'uin' => $user->uin,
                    'username' => $user->username,
                ],
                'access_token' => $token,
                'token_type' => 'bearer',
                'expires_in' => config('jwt.ttl') * 60
            ],
            Response::HTTP_CREATED
        );
    }

    /**
     * @OA\Post(
     *     path="/api/v1/login",
     *     summary="Authenticate user - Step 1 (Send confirmation email)",
     *     tags={"Authentication"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"login", "password"},
     *             @OA\Property(property="login", type="string", description="Email or UIN", example="john@example.com"),
     *             @OA\Property(property="password", type="string", format="password", example="password"),
     *             @OA\Property(property="device_name", type="string", example="iPhone", nullable=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Check your email for confirmation link",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Confirmation email sent. Link is valid for 3 hours."),
     *             @OA\Property(property="requires_confirmation", type="boolean", example=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Login on known device - token issued immediately",
     *         @OA\JsonContent(
     *             @OA\Property(property="access_token", type="string"),
     *             @OA\Property(property="token_type", type="string", example="bearer"),
     *             @OA\Property(property="expires_in", type="integer"),
     *             @OA\Property(property="requires_confirmation", type="boolean", example=false)
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Invalid credentials"
     *     )
     * )
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $data = $request->validated();
        $loginService = new LoginService();

        // Ищем пользователя по email или UIN
        $user = User::where('email', $data['login'])
            ->orWhere('uin', $data['login'])
            ->first();

        if (!$user || !Hash::check($data['password'], $user->password)) {
            return response()->json(
                ['error' => 'Invalid credentials'],
                Response::HTTP_UNAUTHORIZED
            );
        }

        $deviceName = $data['device_name'] ?? 'Unknown Device';
        $userAgent = request()->header('User-Agent');
        $ipAddress = request()->ip();

        // Проверяем, это новое устройство или нет
        $isNewDevice = $loginService->isNewDevice($user, $userAgent, $ipAddress);

        if ($isNewDevice) {
            // Новое устройство - требуем подтверждение по email
            $loginService->createLoginToken(
                $user,
                $deviceName,
                $ipAddress,
                $userAgent
            );

            return response()->json([
                'message' => 'Confirmation email sent. Link is valid for 3 hours.',
                'requires_confirmation' => true,
            ]);
        }

        // Известное устройство - выдаем токен сразу
        $token = JWTAuth::fromUser($user);

        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => config('jwt.ttl') * 60,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'uin' => $user->uin,
                'username' => $user->username,
            ],
            'requires_confirmation' => false,
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/v1/login/confirm",
     *     summary="Confirm login - Step 2 (Verify email token)",
     *     tags={"Authentication"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"token"},
     *             @OA\Property(property="token", type="string", example="random_token_from_email")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Login confirmed, JWT token issued",
     *         @OA\JsonContent(
     *             @OA\Property(property="access_token", type="string"),
     *             @OA\Property(property="token_type", type="string", example="bearer"),
     *             @OA\Property(property="expires_in", type="integer")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Token is invalid or expired"
     *     )
     * )
     */
    public function confirmLogin(ConfirmLoginRequest $request): JsonResponse
    {
        $data = $request->validated();
        $loginService = new LoginService();

        $jwtToken = $loginService->confirmLoginAndGetToken($data['token']);

        if (!$jwtToken) {
            return response()->json(
                ['error' => 'Invalid or expired token'],
                Response::HTTP_BAD_REQUEST
            );
        }

        return response()->json([
            'access_token' => $jwtToken,
            'token_type' => 'bearer',
            'expires_in' => config('jwt.ttl') * 60,
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/login/confirm/{token}",
     *     summary="Confirm login via link (web browser)",
     *     tags={"Authentication"},
     *     @OA\Parameter(
     *         name="token",
     *         in="path",
     *         required=true,
     *         description="Confirmation token from email",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=302,
     *         description="Redirect to app with token in URL"
     *     )
     * )
     */
    public function confirmLoginWeb(string $token): JsonResponse
    {
        $loginService = new LoginService();
        $jwtToken = $loginService->confirmLoginAndGetToken($token);

        if (!$jwtToken) {
            return response()->json(
                ['error' => 'Invalid or expired token'],
                Response::HTTP_BAD_REQUEST
            );
        }

        // В реальности нужно перенаправить на мобильное приложение
        // например: redirect(config('app.deeplink_url') . '?token=' . $jwtToken);
        return response()->json([
            'access_token' => $jwtToken,
            'token_type' => 'bearer',
            'expires_in' => config('jwt.ttl') * 60,
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/v1/logout",
     *     summary="Log the user out (Invalidate the token)",
     *     tags={"Authentication"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Successfully logged out",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Successfully logged out")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthenticated.")
     *         )
     *     )
     * )
     */
    public function logout(): JsonResponse
    {
        JWTAuth::invalidate(JWTAuth::getToken());

        return response()->json(['message' => 'Successfully logged out']);
    }

    /**
     * @OA\Post(
     *     path="/api/v1/refresh",
     *     summary="Refresh a JWT token",
     *     tags={"Authentication"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Token refreshed successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="access_token", type="string", example="eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9..."),
     *             @OA\Property(property="token_type", type="string", example="bearer"),
     *             @OA\Property(property="expires_in", type="integer", example=3600)
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthenticated.")
     *         )
     *     )
     * )
     */
    public function refresh(): JsonResponse
    {
        return $this->respondWithToken(JWTAuth::refresh(JWTAuth::getToken()));
    }

    /**
     * @OA\Get(
     *     path="/api/v1/me",
     *     summary="Get the authenticated User",
     *     tags={"Authentication"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="User details",
     *         @OA\JsonContent(ref="#/components/schemas/User")
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthenticated.")
     *         )
     *     )
     * )
     */
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
