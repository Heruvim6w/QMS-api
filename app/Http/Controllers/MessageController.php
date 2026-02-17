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
 *     description="API Endpoints for Messages"
 * )
 */
class MessageController extends Controller
{
    public function __construct(
        protected EncryptionService $encryptionService,
        protected ChatService $chatService
    ) {}

    /**
     * @OA\Post(
     *     path="/api/v1/messages",
     *     summary="Send an encrypted message",
     *     tags={"Messages"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="chat_id", type="integer", example=1, description="ID чата (если уже есть)"),
     *             @OA\Property(property="receiver_id", type="integer", example=2, description="ID получателя (если новый чат)"),
     *             @OA\Property(property="content", type="string", example="Hello, how are you?"),
     *             @OA\Property(property="type", type="string", enum={"text", "image", "voice", "video", "file"}, example="text")
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
     *     @OA\Response(response=403, description="Access denied"),
     *     @OA\Response(response=404, description="Chat or receiver not found"),
     *     @OA\Response(response=422, description="Validation error")
     * )
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
     *     summary="Get a specific message",
     *     tags={"Messages"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Message ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Message details",
     *         @OA\JsonContent(ref="#/components/schemas/Message")
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Access denied",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="Access denied")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Message not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="Message not found")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Decryption error",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="Failed to decrypt message")
     *         )
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
     *     summary="Get messages in a chat",
     *     tags={"Messages"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="chat_id",
     *         in="query",
     *         required=true,
     *         description="ID of the chat",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="List of messages in chat",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(ref="#/components/schemas/Message")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Missing chat_id parameter",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="chat_id parameter is required")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Access denied to this chat",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="Access denied")
     *         )
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
     *     summary="Upload a file for a message",
     *     tags={"Messages"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Message ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 required={"file"},
     *                 @OA\Property(
     *                     property="file",
     *                     type="string",
     *                     format="binary",
     *                     description="File to upload"
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="File uploaded successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="file_uploaded"),
     *             @OA\Property(property="attachment_id", type="integer", example=1),
     *             @OA\Property(property="file_path", type="string", example="uploads/file.jpg")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Access denied",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="Access denied")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Message not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="Message not found")
     *         )
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
    }}
