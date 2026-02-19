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

/**
 * @OA\Tag(
 *     name="Chats",
 *     description="Chat management endpoints for creating, updating, and managing private chats, group chats, and favorites"
 * )
 */
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
     *     operationId="listChats",
     *     summary="Get all chats for the authenticated user",
     *     description="Retrieve a list of all chats the user participates in, including private chats, group chats, and favorites. Each chat includes participants, last message, and unread count.",
     *     tags={"Chats"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="type",
     *         in="query",
     *         description="Filter by chat type (private, group, favorites)",
     *         @OA\Schema(type="string", enum={"private", "group", "favorites"})
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="List of chats",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(ref="#/components/schemas/ChatResponse")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
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
     *     operationId="createChat",
     *     summary="Create a new group chat",
     *     description="Create a new group chat with specified name and initial participants. Creator automatically becomes chat member. Only group chats can be explicitly created; private chats are created when first message is sent.",
     *     tags={"Chats"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         description="Group chat details",
     *         @OA\JsonContent(
     *             required={"name", "user_ids"},
     *             @OA\Property(property="name", type="string", minLength=1, maxLength=255, example="Project Discussion", description="Name for the group chat"),
     *             @OA\Property(
     *                 property="user_ids",
     *                 type="array",
     *                 @OA\Items(type="string", format="uuid"),
     *                 minItems=1,
     *                 example={"550e8400-e29b-41d4-a716-446655440001", "550e8400-e29b-41d4-a716-446655440002"},
     *                 description="List of user UUIDs to add to the chat (creator is added automatically)"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Group chat created successfully",
     *         @OA\JsonContent(ref="#/components/schemas/ChatResponse")
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
     *     operationId="getChat",
     *     summary="Get detailed chat information",
     *     description="Retrieve complete details of a specific chat including all participants, messages count, and settings. Only accessible to chat members.",
     *     tags={"Chats"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Chat ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Chat details",
     *         @OA\JsonContent(ref="#/components/schemas/ChatResponse")
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Access denied - user is not a member of this chat"
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
     *     operationId="updateChat",
     *     summary="Update a group chat name",
     *     description="Change the name of a group chat. Only the chat creator or admins can update the chat name. Private chats cannot be renamed.",
     *     tags={"Chats"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Chat ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         description="New chat name",
     *         @OA\JsonContent(
     *             required={"name"},
     *             @OA\Property(property="name", type="string", minLength=1, maxLength=255, example="New Chat Name", description="New name for the group chat")
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
     *         description="Access denied - only creator can update or invalid chat type"
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
     *     operationId="leaveChat",
     *     summary="Leave a chat",
     *     description="Remove the current user from a chat. Once left, user will no longer receive messages from this chat. Cannot leave private chats with only 2 members (delete instead). Other participants remain in the chat.",
     *     tags={"Chats"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Chat ID",
     *         @OA\Schema(type="integer", example=1)
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
     *         description="Cannot leave this chat type (e.g., only 2 members in private chat)"
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
     *     operationId="addUserToChat",
     *     summary="Add a user to a group chat",
     *     description="Add a new member to an existing group chat. Only chat creator or admins can add users. Cannot add user to private chats.",
     *     tags={"Chats"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Chat ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         description="User to add",
     *         @OA\JsonContent(
     *             required={"user_id"},
     *             @OA\Property(property="user_id", type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440001", description="UUID of user to add")
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
     *         description="Cannot add user to this chat type (e.g., private chat)"
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
     *     operationId="removeUserFromChat",
     *     summary="Remove a user from a group chat",
     *     description="Remove a member from a group chat. Only chat creator can remove users. User can be notified about removal. Cannot remove from private chats.",
     *     tags={"Chats"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Chat ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Parameter(
     *         name="userId",
     *         in="path",
     *         required=true,
     *         description="User ID (UUID) to remove",
     *         @OA\Schema(type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440001")
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
     *         description="Cannot remove user from this chat type (e.g., private chat)"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Chat or user not found"
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
     *     operationId="toggleMuteChat",
     *     summary="Mute or unmute a chat",
     *     description="Enable or disable notifications for a specific chat. When muted, new messages don't trigger notifications for the current user. Chat remains visible in chat list.",
     *     tags={"Chats"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Chat ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         description="Mute setting",
     *         @OA\JsonContent(
     *             required={"is_muted"},
     *             @OA\Property(property="is_muted", type="boolean", example=true, description="true to mute, false to unmute")
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
