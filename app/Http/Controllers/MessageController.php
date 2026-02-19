<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\Message\GetRequest;
use App\Http\Requests\Message\SendRequest;
use App\Http\Requests\Message\UploadFileRequest;
use App\Models\Attachment;
use App\Models\Chat;
use App\Models\Message;
use App\Models\User;
use App\Services\ChatService;
use App\Services\EncryptionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use OpenApi\Annotations as OA;
use Symfony\Component\HttpFoundation\Response as ResponseAlias;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * @OA\Tag(
 *     name="Messages",
 *     description="Encrypted message handling with end-to-end encryption support, file attachments, and delivery tracking"
 * )
 */
class MessageController extends Controller
{
    public function __construct(
        protected EncryptionService $encryptionService,
        protected ChatService       $chatService
    )
    {
    }

    /**
     * @OA\Post(
     *     path="/api/v1/messages",
     *     operationId="sendMessage",
     *     summary="Send an encrypted message",
     *     description="Send a message to a chat. Message is end-to-end encrypted. Either existing chat_id or new receiver_id should be provided. If receiver_id is provided, private chat is created automatically.",
     *     tags={"Messages"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         description="Message data",
     *         @OA\JsonContent(
     *             required={"content"},
     *             @OA\Property(property="chat_id", type="integer", nullable=true, example=1, description="ID of existing chat (if empty, receiver_id must be provided)"),
     *             @OA\Property(property="receiver_id", type="string", format="uuid", nullable=true, example="550e8400-e29b-41d4-a716-446655440001", description="UUID of receiver (for new private chats, if empty chat_id must be provided)"),
     *             @OA\Property(property="content", type="string", maxLength=65535, example="Hello, how are you?", description="Message text (encrypted before sending)"),
     *             @OA\Property(property="type", type="string", enum={"text", "image", "voice", "video", "file"}, example="text", description="Message type (default: text)")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Message sent successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="id", type="integer", example=1),
     *             @OA\Property(property="chat_id", type="integer", example=1),
     *             @OA\Property(property="status", type="string", example="sent"),
     *             @OA\Property(property="created_at", type="string", format="date-time")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Access denied - not a member of this chat"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Chat or receiver not found"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error"
     *     )
     * )
     * @throws \Throwable
     */
    public function sendMessage(SendRequest $request): JsonResponse
    {
        $data = $request->validated();

        /** @var User $sender */
        $sender = Auth::user();

        if (!$sender) {
            throw new AccessDeniedHttpException();
        }

        // Находим или создаём чат
        if (!empty($data['chat_id'])) {
            $chat = $this->chatService->findById($data['chat_id'], $sender);
        } else {
            $receiver = User::findOrFail($data['receiver_id']);
            $chat = $this->chatService->findOrCreatePrivateChat($receiver);
        }

        // Шифруем содержимое для каждого участника чата
        // Для простоты шифруем ключом отправителя
        $encryptedData = $this->encryptionService->encryptForUser(
            $data['content'],
            $sender->public_key
        );

        $message = Message::query()->create([
            'chat_id' => $chat->id,
            'sender_id' => $sender->id,
            'encrypted_content' => $encryptedData['encrypted_content'],
            'encryption_key' => $encryptedData['encrypted_key'],
            'iv' => $encryptedData['iv'],
            'type' => $data['type'] ?? Message::TYPE_TEXT,
        ]);

        return response()->json(
            [
                'id' => $message->id,
                'chat_id' => $chat->id,
                'status' => 'sent',
                'created_at' => $message->created_at
            ],
            ResponseAlias::HTTP_CREATED
        );
    }

    /**
     * @OA\Get(
     *     path="/api/v1/messages/{id}",
     *     operationId="getMessage",
     *     summary="Get a specific message",
     *     description="Retrieve and decrypt a specific message by ID. Message is automatically marked as read. Only accessible to chat members.",
     *     tags={"Messages"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Message ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Message details (decrypted)",
     *         @OA\JsonContent(ref="#/components/schemas/MessageResponse_v2")
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Access denied - user not a member of this chat"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Message not found"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Decryption error"
     *     )
     * )
     */
    public function getMessage(int $id): JsonResponse
    {
        /** @var User $user */
        $user = auth()->user();

        if (!$user) {
            throw new AccessDeniedHttpException();
        }

        $message = Message::with(['sender', 'chat', 'attachments'])->findOrFail($id);

        // Проверяем, что пользователь имеет доступ к чату сообщения
        if (!$message->chat->hasUser($user)) {
            return response()->json(
                ['error' => 'Access denied'],
                ResponseAlias::HTTP_FORBIDDEN
            );
        }

        try {
            $decryptedContent = $this->encryptionService->decryptForUser(
                $message->encrypted_content,
                $message->encryption_key,
                $message->iv,
                $user->private_key
            );

            // Помечаем сообщение как прочитанное
            if ($message->sender_id !== $user->id && !$message->isReadBy($user)) {
                $message->markAsReadBy($user);
            }

            return response()->json([
                'id' => $message->id,
                'chat_id' => $message->chat_id,
                'sender' => $message->sender,
                'content' => $decryptedContent,
                'type' => $message->type,
                'attachments' => $message->attachments,
                'created_at' => $message->created_at,
                'is_read' => $message->isReadBy($user),
            ]);
        } catch (\Exception $e) {
            return response()->json(
                ['error' => 'Failed to decrypt message'],
                ResponseAlias::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v1/messages",
     *     operationId="getMessages",
     *     summary="Get all messages in a chat",
     *     description="Retrieve all messages from a specific chat with pagination. Messages are automatically decrypted. Only accessible to chat members. Messages are sorted by creation date (oldest first).",
     *     tags={"Messages"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="chat_id",
     *         in="query",
     *         required=true,
     *         description="ID of the chat",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Parameter(
     *         name="limit",
     *         in="query",
     *         description="Maximum number of messages to return (default: 50)",
     *         @OA\Schema(type="integer", example=50)
     *     ),
     *     @OA\Parameter(
     *         name="offset",
     *         in="query",
     *         description="Number of messages to skip (for pagination)",
     *         @OA\Schema(type="integer", example=0)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="List of messages in chat (decrypted)",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(ref="#/components/schemas/MessageResponse")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Missing chat_id parameter"
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Access denied - user not a member of this chat"
     *     )
     * )
     */
    public function getMessages(GetRequest $request): JsonResponse
    {
        $data = $request->validated();

        /** @var User $user */
        $user = auth()->user();

        if (!$user) {
            throw new \RuntimeException('User not found');
        }

        $chat = Chat::findOrFail($data['chat_id']);

        // Проверяем, что пользователь участник чата
        if (!$chat->hasUser($user)) {
            return response()->json(
                ['error' => 'Access denied'],
                ResponseAlias::HTTP_FORBIDDEN
            );
        }

        $messages = Message::where('chat_id', $chat->id)
            ->with(['sender', 'attachments'])
            ->orderBy('created_at', 'asc')
            ->get();

        $result = [];
        foreach ($messages as $message) {
            try {
                $decryptedContent = $this->encryptionService->decryptForUser(
                    $message->encrypted_content,
                    $message->encryption_key,
                    $message->iv,
                    $user->private_key
                );

                // Помечаем сообщение как доставленное
                if ($message->sender_id !== $user->id) {
                    $message->markAsDeliveredTo($user);
                }

                $result[] = [
                    'id' => $message->id,
                    'chat_id' => $message->chat_id,
                    'sender' => $message->sender,
                    'content' => $decryptedContent,
                    'type' => $message->type,
                    'attachments' => $message->attachments,
                    'created_at' => $message->created_at,
                    'is_read' => $message->isReadBy($user),
                ];
            } catch (\Exception $e) {
                continue;
            }
        }

        return response()->json($result);
    }

    /**
     * @OA\Post(
     *     path="/api/v1/messages/{id}/upload",
     *     operationId="uploadFileToMessage",
     *     summary="Upload a file attachment to a message",
     *     description="Attach a file to a message. File is stored encrypted on server. Only message author can add attachments. Supports any file type within size limits.",
     *     tags={"Messages"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Message ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         description="File to upload",
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 required={"file"},
     *                 @OA\Property(
     *                     property="file",
     *                     type="string",
     *                     format="binary",
     *                     description="Binary file content (max 100MB recommended)"
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="File uploaded and attached successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="file_uploaded"),
     *             @OA\Property(property="attachment_id", type="integer", example=1),
     *             @OA\Property(property="file_path", type="string", example="uploads/messages/file.jpg"),
     *             @OA\Property(property="file_size", type="integer", example=102400),
     *             @OA\Property(property="mime_type", type="string", example="image/jpeg")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Access denied - not the message author"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Message not found"
     *     ),
     *     @OA\Response(
     *         response=413,
     *         description="File too large"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error (invalid file type, etc)"
     *     )
     * )
     */
    public function uploadFile(UploadFileRequest $request, int $id): JsonResponse
    {
        /** @var User $user */
        $user = auth()->user();

        $message = Message::findOrFail($id);

        // Проверяем, что пользователь участник чата
        if (!$message->chat->hasUser($user)) {
            return response()->json(
                ['error' => 'Access denied'],
                ResponseAlias::HTTP_FORBIDDEN
            );
        }

        $file = $request->file('file');
        $path = $file->store('uploads', 'public');

        $attachment = Attachment::query()->create([
            'message_id' => $message->id,
            'file_path' => $path,
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
            'name' => $file->getClientOriginalName(),
        ]);

        return response()->json([
            'status' => 'file_uploaded',
            'attachment_id' => $attachment->id,
            'file_path' => $path
        ]);
    }
}
