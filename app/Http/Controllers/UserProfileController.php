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
 *     description="User profile management, status management, session management, and user search"
 * )
 */
class UserProfileController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/v1/users/profile",
     *     operationId="getProfile",
     *     summary="Get current user profile",
     *     description="Retrieve the complete profile of the authenticated user including personal data, UIN, username, status, and created date.",
     *     tags={"Users"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="User profile data",
     *         @OA\JsonContent(ref="#/components/schemas/UserProfileResponse")
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
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
     *     operationId="setUsername",
     *     summary="Set or update custom username",
     *     description="Create or change a custom unique username (like in Telegram). Username must be 3-20 characters, Latin letters, digits, underscore, and dash only. Can be changed anytime. UIN remains unchanged.",
     *     tags={"Users"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         description="New username",
     *         @OA\JsonContent(
     *             required={"username"},
     *             @OA\Property(property="username", type="string", minLength=3, maxLength=20, pattern="^[a-zA-Z0-9_-]+$", example="john_doe")
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
     *             @OA\Property(property="error", type="string", example="Username must contain 3-20 characters (letters, digits, underscore, dash)")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error or username already taken"
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
     *     operationId="searchUser",
     *     summary="Search user by UIN or username",
     *     description="Search for a user by their 8-digit UIN or custom username. Can be used to find users for adding to contacts or group chats. Returns basic user profile information.",
     *     tags={"Users"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="query",
     *         in="query",
     *         required=true,
     *         description="Search query - either UIN (8 digits) or username (3-20 chars, latin only)",
     *         @OA\Schema(type="string", minLength=3, example="12345678")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="User found",
     *         @OA\JsonContent(ref="#/components/schemas/SearchUserResponse")
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Invalid search query format",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="Invalid search query")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="User not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="User not found")
     *         )
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
     *     operationId="getUserByIdentifier",
     *     summary="Get user profile by UIN or username",
     *     description="Retrieve detailed user profile information using their UIN (8-digit identifier) or custom username. Returns public profile information including status and last seen time.",
     *     tags={"Users"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="identifier",
     *         in="path",
     *         required=true,
     *         description="User identifier - either UIN (8 digits) or username (3-20 chars)",
     *         @OA\Schema(type="string", example="john_doe")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="User profile data",
     *         @OA\JsonContent(ref="#/components/schemas/SearchUserResponse")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="User not found with the given identifier"
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
     *     operationId="getSessions",
     *     summary="Get all active sessions for current user",
     *     description="Retrieve a list of all currently active sessions (confirmed login devices). Each session contains device info, confirmation date, and expiration date. Helps user track and manage their connected devices.",
     *     tags={"Users"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="List of active sessions across all devices",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", description="Session ID"),
     *                 @OA\Property(property="device_name", type="string", example="iPhone 13", description="User-friendly device name"),
     *                 @OA\Property(property="ip_address", type="string", format="ipv4", example="192.168.1.1", description="IP address where device logged in"),
     *                 @OA\Property(property="confirmed_at", type="string", format="date-time", description="When this device was confirmed"),
     *                 @OA\Property(property="expires_at", type="string", format="date-time", description="When this session expires")
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
     *     operationId="endSession",
     *     summary="End a specific session (logout from device)",
     *     description="Terminate a specific session and logout from that device. Other active sessions remain unaffected. The session will no longer be able to use their JWT tokens.",
     *     tags={"Users"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="sessionId",
     *         in="path",
     *         required=true,
     *         description="Unique session identifier to terminate",
     *         @OA\Schema(type="integer", example=42)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Session successfully terminated",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="Session ended")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Session not found or does not belong to current user"
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
     *     operationId="setStatus",
     *     summary="Set user online status and custom status text",
     *     description="Update the user's online status (mood) and optional custom status text. User can only select offline status indirectly (via inactivity). Custom status supports emojis and has a max of 50 characters. Status is displayed to friends in their contact list.",
     *     tags={"Users"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         description="Status information to set",
     *         @OA\JsonContent(
     *             required={"online_status"},
     *             @OA\Property(
     *                 property="online_status",
     *                 type="string",
     *                 enum={"online", "chatty", "angry", "depressed", "home", "work", "eating", "away", "unavailable", "busy", "do_not_disturb"},
     *                 example="chatty",
     *                 description="Selected mood/status"
     *             ),
     *             @OA\Property(
     *                 property="custom_status",
     *                 type="string",
     *                 description="Optional custom status text (max 50 characters, supports emoji and 😀)",
     *                 example="На встрече 🎯",
     *                 nullable=true,
     *                 maxLength=50
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Status updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="online_status", type="string", example="chatty", description="Set online status"),
     *             @OA\Property(property="display_status", type="string", example="Готов поболтать - На встрече 🎯", description="Combined display string with localization")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error (invalid status, custom status too long, etc)"
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
     *     operationId="getAvailableStatuses",
     *     summary="Get list of available online statuses",
     *     description="Retrieve list of all available online statuses (moods) that user can select from. Includes both localized display names and status keys for client-side usage.",
     *     tags={"Users"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="List of available online statuses with localized names",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="statuses",
     *                 type="object",
     *                 description="Key-value pairs of status key and localized display name",
     *                 additionalProperties=@OA\Property(type="string")
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
     *     operationId="getUserStatus",
     *     summary="Get user status (for friends/contacts list)",
     *     description="Retrieve the current status of a user, including whether they are online and their selected status mood. If offline, shows time of last seen. Used for displaying user status in contacts list.",
     *     tags={"Users"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="identifier",
     *         in="path",
     *         required=true,
     *         description="User UIN (8 digits) or username",
     *         @OA\Schema(type="string", example="john_doe")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="User status information",
     *         @OA\JsonContent(
     *             @OA\Property(property="id", type="string", format="uuid"),
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="uin", type="string"),
     *             @OA\Property(property="is_online", type="boolean", description="true if user is online, false if offline"),
     *             @OA\Property(property="status", type="string", example="Готов поболтать", description="Localized status name"),
     *             @OA\Property(property="status_key", type="string", example="chatty", description="Status key for client-side use"),
     *             @OA\Property(property="last_seen", type="string", nullable=true, example="3 hours ago", description="Human-readable last seen time (only if offline)")
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
     *     operationId="getLanguages",
     *     summary="Get list of supported languages",
     *     description="Retrieve all supported languages/locales with their display names and localized status names. Used for language selection UI and status display.",
     *     tags={"Users"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="List of supported languages and status names in each language",
     *         @OA\JsonContent(
     *             @OA\Property(property="supported_locales", type="array", items={"type": "string"}, example={"en", "ru", "de"}, description="Available language codes"),
     *             @OA\Property(property="current_locale", type="string", example="en", description="Current user's language setting"),
     *             @OA\Property(property="language_names", type="object", example={"en": "English", "ru": "Русский", "de": "Deutsch"}, description="Language names in their native language"),
     *             @OA\Property(property="status_names", type="object", example={"online": "Online", "chatty": "Chatty", "angry": "Angry"}, description="Status names in user's current language")
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
     *     operationId="updateLocale",
     *     summary="Update user preferred language",
     *     description="Change the user's interface language preference. Language selection persists across sessions. Affects status display and all UI text. Should match system locale auto-detection where possible.",
     *     tags={"Users"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         description="New language locale",
     *         @OA\JsonContent(
     *             required={"locale"},
     *             @OA\Property(property="locale", type="string", enum={"en", "ru", "de"}, example="ru", description="Language code (2-letter ISO 639-1)")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Language updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="locale", type="string", example="ru"),
     *             @OA\Property(property="language_name", type="string", example="Русский", description="Display name of selected language")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error (unsupported language)",
     *         @OA\JsonContent(ref="#/components/schemas/ValidationError")
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

