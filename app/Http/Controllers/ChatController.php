<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\Chat\AddUserToChatRequest;
use App\Http\Requests\Chat\CreateGroupChatRequest;
use App\Http\Requests\Chat\MuteChatRequest;
use App\Http\Requests\Chat\UpdateChatRequest;
use App\Models\Chat;
use App\Models\User;
use App\Services\ChatService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use OpenApi\Annotations as OA;
use Symfony\Component\HttpFoundation\Response;

class ChatController extends Controller
{
    protected ChatService $chatService;

    public function __construct(ChatService $chatService)
    {
        $this->chatService = $chatService;
    }

    /**
     * @OA\Get(
     *     path="/api/v1/chats",
     *     summary="Get all chats for the authenticated user",
     *     tags={"Chats"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="List of chats",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(ref="#/components/schemas/Chat")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized"
     *     )
     * )
     */
    public function index(): JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();

        $chats = $this->chatService->getUserChats($user);

        return response()->json($chats);
    }

    /**
     * @OA\Post(
     *     path="/api/v1/chats",
     *     summary="Create a new group chat",
     *     tags={"Chats"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string", example="Project Discussion"),
     *             @OA\Property(
     *                 property="user_ids",
     *                 type="array",
     *                 @OA\Items(type="integer"),
     *                 example={2, 3, 4}
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Group chat created successfully",
     *         @OA\JsonContent(ref="#/components/schemas/Chat")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error"
     *     )
     * )
     */
    public function store(CreateGroupChatRequest $request): JsonResponse
    {
        $data = $request->validated();

        $chat = $this->chatService->createGroupChat($data['name'], $data['user_ids']);

        return response()->json($chat, Response::HTTP_CREATED);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/chats/{id}",
     *     summary="Get a specific chat",
     *     tags={"Chats"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Chat ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Chat details",
     *         @OA\JsonContent(ref="#/components/schemas/Chat")
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Access denied"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Chat not found"
     *     )
     * )
     */
    public function show(int $id): JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();

        $chat = $this->chatService->findById($id, $user);

        return response()->json($chat);
    }

    /**
     * @OA\Put(
     *     path="/api/v1/chats/{id}",
     *     summary="Update a group chat name",
     *     tags={"Chats"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Chat ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string", example="New Chat Name")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Chat updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="updated")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Access denied or invalid chat type"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Chat not found"
     *     )
     * )
     */
    public function update(UpdateChatRequest $request, int $id): JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();

        $chat = $this->chatService->findById($id, $user);
        $data = $request->validated();

        try {
            $this->chatService->updateGroupName($chat, $data['name']);

            return response()->json(['status' => 'updated']);
        } catch (\InvalidArgumentException $e) {
            return response()->json(
                ['error' => $e->getMessage()],
                Response::HTTP_FORBIDDEN
            );
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/chats/{id}",
     *     summary="Leave a chat",
     *     tags={"Chats"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Chat ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Left the chat successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="left")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Cannot leave this chat type"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Chat not found"
     *     )
     * )
     */
    public function destroy(int $id): JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();

        $chat = $this->chatService->findById($id, $user);

        try {
            $this->chatService->leaveChat($chat, $user);

            return response()->json(['status' => 'left']);
        } catch (\InvalidArgumentException $e) {
            return response()->json(
                ['error' => $e->getMessage()],
                Response::HTTP_FORBIDDEN
            );
        }
    }

    /**
     * @OA\Post(
     *     path="/api/v1/chats/{id}/add-user",
     *     summary="Add a user to a group chat",
     *     tags={"Chats"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Chat ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="user_id", type="integer", example=5)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="User added successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="user_added")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Cannot add user to this chat type"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Chat or user not found"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error"
     *     )
     * )
     */
    public function addUser(AddUserToChatRequest $request, int $id): JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();

        $chat = $this->chatService->findById($id, $user);
        $data = $request->validated();

        try {
            $userToAdd = User::findOrFail($data['user_id']);
            $this->chatService->addUserToChat($chat, $userToAdd);

            return response()->json(['status' => 'user_added']);
        } catch (\InvalidArgumentException $e) {
            return response()->json(
                ['error' => $e->getMessage()],
                Response::HTTP_FORBIDDEN
            );
        }
    }

    /**
     * @OA\Post(
     *     path="/api/v1/chats/{id}/remove-user/{userId}",
     *     summary="Remove a user from a group chat",
     *     tags={"Chats"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Chat ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="userId",
     *         in="path",
     *         required=true,
     *         description="User ID to remove",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="User removed successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="user_removed")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Cannot remove user from this chat type"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Chat not found"
     *     )
     * )
     */
    public function removeUser(int $id, int $userId): JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();

        $chat = $this->chatService->findById($id, $user);

        try {
            $userToRemove = User::findOrFail($userId);
            $this->chatService->removeUserFromChat($chat, $userToRemove);

            return response()->json(['status' => 'user_removed']);
        } catch (\InvalidArgumentException $e) {
            return response()->json(
                ['error' => $e->getMessage()],
                Response::HTTP_FORBIDDEN
            );
        }
    }

    /**
     * @OA\Post(
     *     path="/api/v1/chats/{id}/mute",
     *     summary="Mute or unmute a chat for the current user",
     *     tags={"Chats"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Chat ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="is_muted", type="boolean", example=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Chat mute status updated",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="mute_updated")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Chat not found"
     *     )
     * )
     */
    public function toggleMute(MuteChatRequest $request, int $id): JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();

        $chat = $this->chatService->findById($id, $user);
        $data = $request->validated();

        $this->chatService->toggleMute($chat, $user, $data['is_muted']);

        return response()->json(['status' => 'mute_updated']);
    }

    /**
     * @OA\Post(
     *     path="/api/v1/chats/get-or-create-private/{userId}",
     *     summary="Get or create a private chat with a specific user",
     *     tags={"Chats"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="userId",
     *         in="path",
     *         required=true,
     *         description="User ID to create private chat with",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Private chat retrieved or created",
     *         @OA\JsonContent(ref="#/components/schemas/Chat")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="User not found"
     *     )
     * )
     */
    public function getOrCreatePrivateChat(int $userId): JsonResponse
    {
        $receiver = User::findOrFail($userId);

        $chat = $this->chatService->findOrCreatePrivateChat($receiver);

        return response()->json($chat);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/chats/favorites/get-or-create",
     *     summary="Get or create a favorites chat",
     *     tags={"Chats"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Favorites chat retrieved or created",
     *         @OA\JsonContent(ref="#/components/schemas/Chat")
     *     )
     * )
     */
    public function getOrCreateFavoritesChat(): JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();

        $chat = $this->chatService->getOrCreateFavoritesChat($user);

        return response()->json($chat);
    }
}
