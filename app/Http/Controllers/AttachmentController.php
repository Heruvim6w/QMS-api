<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\Attachment\DeleteAttachmentRequest;
use App\Http\Requests\Attachment\DownloadAttachmentRequest;
use App\Models\Attachment;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use OpenApi\Annotations as OA;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @OA\Tag(
 *     name="Attachments",
 *     description="API Endpoints for Message Attachments"
 * )
 */
class AttachmentController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/v1/attachments/{id}",
     *     summary="Get attachment details",
     *     tags={"Attachments"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Attachment ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Attachment details",
     *         @OA\JsonContent(ref="#/components/schemas/Attachment")
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
     *         description="Attachment not found"
     *     )
     * )
     */
    public function show(int $id): JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();

        if (!$user) {
            throw new AccessDeniedHttpException();
        }

        $attachment = Attachment::with('message.chat')->findOrFail($id);

        // Проверяем, что пользователь участник чата, которому принадлежит сообщение
        if (!$attachment->message->chat->hasUser($user)) {
            throw new AccessDeniedHttpException('Нет доступа к этому вложению');
        }

        return response()->json($attachment);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/attachments/{id}/download",
     *     summary="Download an attachment file",
     *     tags={"Attachments"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Attachment ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="File download stream",
     *         @OA\MediaType(mediaType="application/octet-stream")
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Access denied"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Attachment or file not found"
     *     )
     * )
     */
    public function download(DownloadAttachmentRequest $request, int $id): StreamedResponse
    {
        /** @var User $user */
        $user = Auth::user();

        if (!$user) {
            throw new AccessDeniedHttpException();
        }

        $attachment = Attachment::with('message.chat')->findOrFail($id);

        // Проверяем, что пользователь участник чата
        if (!$attachment->message->chat->hasUser($user)) {
            throw new AccessDeniedHttpException('Нет доступа к этому файлу');
        }

        // Проверяем, что файл существует
        if (!Storage::disk('public')->exists($attachment->file_path)) {
            throw new NotFoundHttpException('Файл не найден на сервере');
        }

        $fileName = $attachment->name ?? basename($attachment->file_path);

        return Storage::disk('public')->download(
            $attachment->file_path,
            $fileName,
            ['Content-Type' => $attachment->mime_type]
        );
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/attachments/{id}",
     *     summary="Delete an attachment",
     *     tags={"Attachments"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Attachment ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Attachment deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="deleted")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Access denied - only message author can delete attachments",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="Только автор сообщения может удалять вложения")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Attachment not found"
     *     )
     * )
     */
    public function destroy(DeleteAttachmentRequest $request, int $id): JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();

        if (!$user) {
            throw new AccessDeniedHttpException();
        }

        $attachment = Attachment::with('message')->findOrFail($id);

        // Только автор сообщения может удалить вложение
        if ($attachment->message->sender_id !== $user->id) {
            throw new AccessDeniedHttpException('Только автор сообщения может удалять вложения');
        }

        // Удаляем файл со хранилища
        if (Storage::disk('public')->exists($attachment->file_path)) {
            Storage::disk('public')->delete($attachment->file_path);
        }

        // Удаляем запись о вложении
        $attachment->delete();

        return response()->json(['status' => 'deleted']);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/messages/{messageId}/attachments",
     *     summary="Get all attachments for a message",
     *     tags={"Attachments"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="messageId",
     *         in="path",
     *         required=true,
     *         description="Message ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="List of attachments for the message",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(ref="#/components/schemas/Attachment")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Access denied"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Message not found"
     *     )
     * )
     */
    public function getMessageAttachments(int $messageId): JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();

        if (!$user) {
            throw new AccessDeniedHttpException();
        }

        $message = \App\Models\Message::with('chat', 'attachments')->findOrFail($messageId);

        // Проверяем, что пользователь участник чата
        if (!$message->chat->hasUser($user)) {
            throw new AccessDeniedHttpException('Нет доступа к сообщению');
        }

        return response()->json($message->attachments);
    }
}
