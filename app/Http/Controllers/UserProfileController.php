<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\User\SearchUserRequest;
use App\Http\Requests\User\SetUsernameRequest;
use App\Models\User;
use App\Services\LoginService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use OpenApi\Annotations as OA;
use Symfony\Component\HttpFoundation\Response;

/**
 * @OA\Tag(
 *     name="Users",
 *     description="API Endpoints for User Management"
 * )
 */
class UserProfileController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/v1/users/profile",
     *     summary="Get current user profile",
     *     tags={"Users"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="User profile",
     *         @OA\JsonContent(ref="#/components/schemas/User")
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized"
     *     )
     * )
     */
    public function getProfile(): JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();

        return response()->json([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'uin' => $user->uin,
            'username' => $user->username,
            'created_at' => $user->created_at,
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/v1/users/username",
     *     summary="Set or update username",
     *     tags={"Users"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="username", type="string", example="john_doe")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Username updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="username", type="string", example="john_doe")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Invalid username format",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     )
     * )
     */
    public function setUsername(SetUsernameRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();

        $data = $request->validated();

        $user->update(['username' => $data['username']]);

        return response()->json([
            'status' => 'success',
            'username' => $user->username,
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/users/search",
     *     summary="Search user by UIN or username",
     *     tags={"Users"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="query",
     *         in="query",
     *         required=true,
     *         description="UIN (8 digits) or username to search",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="User found",
     *         @OA\JsonContent(
     *             @OA\Property(property="id", type="string", format="uuid"),
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="uin", type="string"),
     *             @OA\Property(property="username", type="string", nullable=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Invalid search query",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="User not found"
     *     )
     * )
     */
    public function searchUser(SearchUserRequest $request): JsonResponse
    {
        $data = $request->validated();
        $query = $data['query'];

        $user = User::findByIdentifier($query);

        if (!$user) {
            return response()->json(
                ['error' => 'User not found'],
                Response::HTTP_NOT_FOUND
            );
        }

        return response()->json([
            'id' => $user->id,
            'name' => $user->name,
            'uin' => $user->uin,
            'username' => $user->username,
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/users/{identifier}",
     *     summary="Get user by UIN or username",
     *     tags={"Users"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="identifier",
     *         in="path",
     *         required=true,
     *         description="User UIN (8 digits) or username",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="User profile",
     *         @OA\JsonContent(
     *             @OA\Property(property="id", type="string", format="uuid"),
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="uin", type="string"),
     *             @OA\Property(property="username", type="string", nullable=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="User not found"
     *     )
     * )
     */
    public function getUserByIdentifier(string $identifier): JsonResponse
    {
        $user = User::findByIdentifier($identifier);

        if (!$user) {
            return response()->json(
                ['error' => 'User not found'],
                Response::HTTP_NOT_FOUND
            );
        }

        return response()->json([
            'id' => $user->id,
            'name' => $user->name,
            'uin' => $user->uin,
            'username' => $user->username,
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/sessions",
     *     summary="Get all active sessions for current user",
     *     tags={"Users"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="List of active sessions",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 @OA\Property(property="id", type="integer"),
     *                 @OA\Property(property="device_name", type="string"),
     *                 @OA\Property(property="ip_address", type="string"),
     *                 @OA\Property(property="confirmed_at", type="string", format="date-time"),
     *                 @OA\Property(property="expires_at", type="string", format="date-time")
     *             )
     *         )
     *     )
     * )
     */
    public function getSessions(): JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();

        $loginService = new LoginService();
        $sessions = $loginService->getConfirmedSessions($user);

        return response()->json($sessions->map(function ($session) {
            return [
                'id' => $session->id,
                'device_name' => $session->device_name,
                'ip_address' => $session->ip_address,
                'confirmed_at' => $session->confirmed_at,
                'expires_at' => $session->expires_at,
            ];
        }));
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/sessions/{sessionId}",
     *     summary="End a session (logout from device)",
     *     tags={"Users"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="sessionId",
     *         in="path",
     *         required=true,
     *         description="Session ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Session ended successfully"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Session not found"
     *     )
     * )
     */
    public function endSession(int $sessionId): JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();

        $loginService = new LoginService();

        if ($loginService->endSession($user, $sessionId)) {
            return response()->json(['status' => 'Session ended']);
        }

        return response()->json(
            ['error' => 'Session not found'],
            Response::HTTP_NOT_FOUND
        );
    }
}
