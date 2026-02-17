<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\Message\GetRequest;
use App\Http\Requests\Message\SendRequest;
use App\Http\Requests\Message\UploadFileRequest;
use App\Models\Message;
use App\Models\User;
use App\Services\ChatService;
use App\Services\EncryptionService;
use Illuminate\Support\Facades\Auth;
use OpenApi\Annotations as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
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
    protected EncryptionService $encryptionService;
    protected ChatService $chatService;

    public function __construct(EncryptionService $encryptionService, ChatService $chatService)
    {
        $this->encryptionService = $encryptionService;
        $this->chatService = $chatService;
    }

    /**
     * @OA\Post(
     *     path="/api/v1/messages",
     *     summary="Send an encrypted message",
     *     tags={"Messages"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"receiver_id", "content"},
     *             @OA\Property(property="receiver_id", type="integer", example=2),
     *             @OA\Property(property="content", type="string", example="Hello, how are you?"),
     *             @OA\Property(property="type", type="string", enum={"text", "image", "voice", "video", "file"}, example="text")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Message sent successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="id", type="integer", example=1),
     *             @OA\Property(property="status", type="string", example="sent"),
     *             @OA\Property(property="created_at", type="string", format="date-time")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="The given data was invalid."),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Receiver not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="Receiver not found")
     *         )
     *     )
     * )
     */
    public function sendMessage(SendRequest $request): JsonResponse
    {
        $data = $request->validated();

        $sender = Auth::user();

        if (!$sender) {
            throw new AccessDeniedHttpException();
        }

        $receiver = User::findOrFail($data['receiver_id']);
        $chat = $this->findOrCreateChat($receiver, $data['chat_id']);

        $encryptedData = $this->encryptionService->encryptForUser(
            $data['content'],
            $receiver->public_key
        );

        $message = Message::create([
            'sender_id' => $sender->id,
            'receiver_id' => $receiver->id,
            'encrypted_content' => $encryptedData['encrypted_content'],
            'encryption_key' => $encryptedData['encrypted_key'],
            'iv' => $encryptedData['iv'],
            'type' => $data->type ?? 'text',
        ]);

        return response()->json(
            [
                'id' => $message->id,
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
    public function getMessage(int $id): \Illuminate\Http\JsonResponse
    {
        $user = auth()->user();

        if (!$user) {
            throw new \RuntimeException('User not found');
        }

        $message = Message::with(['sender', 'receiver'])->findOrFail($id);

        if (!$message) {
            throw new \RuntimeException('Message not found');
        }

        if ($message->sender_id !== $user->id && $message->receiver_id !== $user->id) {
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

            // Помечаем сообщение как прочитанное, если получатель
            if ($message->receiver_id === $user->id && !$message->read_at) {
                $message->update(['read_at' => now()]);
            }

            return response()->json([
                'id' => $message->id,
                'sender' => $message->sender,
                'receiver' => $message->receiver,
                'content' => $decryptedContent,
                'type' => $message->type,
                'created_at' => $message->created_at,
                'read_at' => $message->read_at,
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
     *     summary="Get conversation between users",
     *     tags={"Messages"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="user_id",
     *         in="query",
     *         required=true,
     *         description="ID of the conversation partner",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="List of messages in conversation",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(ref="#/components/schemas/Message")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Missing user_id parameter",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="user_id parameter is required")
     *         )
     *     )
     * )
     */
    public function getMessages(GetRequest $request): JsonResponse
    {
        $data = $request->validated();

        $user = auth()->user();

        if (!$user) {
            throw new \RuntimeException('User not found');
        }

        $partnerId = $data['user_id'];

        $messages = Message::where(static function ($query) use ($user, $partnerId) {
            $query->where('sender_id', $user->id)
                ->where('receiver_id', $partnerId);
        })->orWhere(function ($query) use ($user, $partnerId) {
            $query->where('sender_id', $partnerId)
                ->where('receiver_id', $user->id);
        })->orderBy('created_at', 'asc')->get();

        $result = [];
        foreach ($messages as $message) {
            try {
                $decryptedContent = $this->encryptionService->decryptForUser(
                    $message->encrypted_content,
                    $message->encryption_key,
                    $message->iv,
                    $user->private_key
                );

                $result[] = [
                    'id' => $message->id,
                    'sender' => $message->sender,
                    'receiver' => $message->receiver,
                    'content' => $decryptedContent,
                    'type' => $message->type,
                    'created_at' => $message->created_at,
                    'read_at' => $message->read_at,
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
        $message = Message::findOrFail($id);

        if (auth()->id() !== $message->sender_id && auth()->id() !== $message->receiver_id) {
            return response()->json(
                ['error' => 'Access denied'],
                ResponseAlias::HTTP_FORBIDDEN
            );
        }

        $file = $request->file('file');
        $path = $file->store('uploads', 'public');

        $message->update(['file_path' => $path]);

        return response()->json([
            'status' => 'file_uploaded',
            'file_path' => $path
        ]);
    }

    private function findOrCreateChat(User $receiver, ?int $chatId = null)
    {
        return $this->chatService->findOrCreate($receiver, $chatId);
    }
}
