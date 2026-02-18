<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\User\SearchUserRequest;
use App\Http\Requests\User\SetStatusRequest;
use App\Http\Requests\User\SetUsernameRequest;
use App\Http\Requests\UpdateUserLocaleRequest;
use App\Models\User;
use App\Services\LocalizationService;
use App\Services\LoginService;
use App\Services\StatusService;
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

    /**
     * @OA\Post(
     *     path="/api/v1/users/status",
     *     summary="Set user online status",
     *     tags={"Users"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"online_status"},
     *             @OA\Property(
     *                 property="online_status",
     *                 type="string",
     *                 enum={"online", "chatty", "angry", "depressed", "home", "work", "eating", "away", "unavailable", "busy", "do_not_disturb"},
     *                 example="chatty"
     *             ),
     *             @OA\Property(
     *                 property="custom_status",
     *                 type="string",
     *                 description="Custom status text (max 50 characters, supports emoji)",
     *                 example="На встрече 🎯",
     *                 nullable=true
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Status updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="online_status", type="string", example="chatty"),
     *             @OA\Property(property="display_status", type="string", example="Готов поболтать - На встрече 🎯")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error"
     *     )
     * )
     */
    public function setStatus(SetStatusRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();

        $data = $request->validated();

        $statusService = new StatusService();
        $statusService->setStatus(
            $user,
            $data['online_status'],
            $data['custom_status'] ?? null
        );

        return response()->json([
            'status' => 'success',
            'online_status' => $user->online_status,
            'display_status' => $user->getDisplayStatus(),
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/users/status/available",
     *     summary="Get available online statuses",
     *     tags={"Users"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="List of available statuses",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="statuses",
     *                 type="object",
     *                 example={"online": "Онлайн", "chatty": "Готов поболтать", "angry": "Злой"}
     *             )
     *         )
     *     )
     * )
     */
    public function getAvailableStatuses(): JsonResponse
    {
        $statuses = StatusService::getAvailableStatuses();

        return response()->json([
            'statuses' => $statuses,
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/users/{identifier}/status",
     *     summary="Get user status (for friends/contacts list)",
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
     *         description="User status information",
     *         @OA\JsonContent(
     *             @OA\Property(property="id", type="string", format="uuid"),
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="uin", type="string"),
     *             @OA\Property(property="is_online", type="boolean"),
     *             @OA\Property(property="status", type="string", example="Готов поболтать"),
     *             @OA\Property(property="status_key", type="string", example="chatty"),
     *             @OA\Property(property="last_seen", type="string", nullable=true, example="3 hours ago")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="User not found"
     *     )
     * )
     */
    public function getUserStatus(string $identifier): JsonResponse
    {
        $user = User::findByIdentifier($identifier);

        if (!$user) {
            return response()->json(
                ['error' => 'User not found'],
                Response::HTTP_NOT_FOUND
            );
        }

        $statusService = new StatusService();
        $status = $statusService->getStatusForFriend($user);

        return response()->json([
            'id' => $user->id,
            'name' => $user->name,
            'uin' => $user->uin,
            ...$status,
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/languages",
     *     summary="Get list of supported languages",
     *     tags={"Users"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="List of supported languages",
     *         @OA\JsonContent(
     *             @OA\Property(property="supported_locales", type="array", items={"type": "string"}, example={"en", "ru", "de"}),
     *             @OA\Property(property="current_locale", type="string", example="en"),
     *             @OA\Property(property="language_names", type="object", example={"en": "English", "ru": "Русский", "de": "Deutsch"}),
     *             @OA\Property(property="status_names", type="object", example={"online": "Online", "chatty": "Chatty", "angry": "Angry"})
     *         )
     *     )
     * )
     */
    public function getLanguages(): JsonResponse
    {
        $localizationService = new LocalizationService();

        return response()->json([
            'supported_locales' => $localizationService->getSupportedLocales(),
            'current_locale' => $localizationService->getCurrentLocale(),
            'language_names' => $localizationService->getLanguageNames(),
            'status_names' => $localizationService->getStatusNames(),
        ]);
    }

    /**
     * @OA\Put(
     *     path="/api/v1/users/locale",
     *     summary="Update user preferred language",
     *     tags={"Users"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"locale"},
     *             @OA\Property(property="locale", type="string", enum={"en", "ru", "de"}, example="ru")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Language updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="locale", type="string", example="ru"),
     *             @OA\Property(property="language_name", type="string", example="Русский")
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
    public function updateLocale(UpdateUserLocaleRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();

        $data = $request->validated();

        $localizationService = new LocalizationService();
        $localizationService->updateUserLocale($user, $data['locale']);

        return response()->json([
            'status' => 'success',
            'locale' => $user->locale,
            'language_name' => __("languages.{$user->locale}"),
        ]);
    }
}

