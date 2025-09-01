<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\Message\GetRequest;
use App\Http\Requests\Message\SendRequest;
use App\Http\Requests\Message\UploadFileRequest;
use App\Models\Message;
use App\Models\User;
use App\Services\EncryptionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response as ResponseAlias;
use function Symfony\Component\String\u;

class MessageController extends Controller
{
    protected EncryptionService $encryptionService;

    public function __construct(EncryptionService $encryptionService)
    {
        $this->encryptionService = $encryptionService;
        $this->middleware('auth:api');
    }

    public function sendMessage(SendRequest $request): JsonResponse
    {
        $data = $request->validated();

        $sender = Auth::user();

        if (!$sender) {
            throw new \RuntimeException('Sender not found');
        }

        $receiver = User::findOrFail($data->receiver_id);

        $encryptedData = $this->encryptionService->encryptForUser(
            $data->content,
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

    public function getMessages(GetRequest $request): JsonResponse
    {
        $data = $request->validated();

        $user = auth()->user();

        if (!$user) {
            throw new \RuntimeException('User not found');
        }

        $partnerId = $data->user_id;

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

    public function uploadFile(UploadFileRequest $request, int $id): JsonResponse
    {
        $message = Message::findOrFail($id);

        if (auth()->id() !== $message->sender_id && auth()->id() !== $message->receiver_id) {
            return response()->json(
                ['error' => 'Access denied'],
                ResponseAlias::HTTP_FORBIDDEN
            );
        }

        $data = $request->validated();

        $file = $data->file('file');
        $path = $file->store('uploads', 'public');

        $message->update(['file_path' => $path]);

        return response()->json([
            'status' => 'file_uploaded',
            'file_path' => $path
        ]);
    }
}
