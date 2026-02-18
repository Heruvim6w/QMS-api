<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use OpenApi\Annotations as OA;

/**
 * @OA\Schema(
 *     schema="Message",
 *     type="object",
 *     title="Message",
 *     description="Message model with end-to-end encryption",
 *     required={"sender_id", "chat_id", "encrypted_content", "iv", "type"},
 *     @OA\Property(
 *         property="id",
 *         type="integer",
 *         format="int64",
 *         description="Unique identifier for the message",
 *         example=1
 *     ),
 *     @OA\Property(
 *         property="chat_id",
 *         type="integer",
 *         format="int64",
 *         description="Identifier for the chat",
 *         example=1
 *     ),
 *     @OA\Property(
 *         property="sender_id",
 *         type="string",
 *         format="uuid",
 *         description="UUID of the user who sent the message",
 *         example="550e8400-e29b-41d4-a716-446655440000"
 *     ),
 *     @OA\Property(
 *         property="encrypted_content",
 *         type="string",
 *         description="Encrypted content of the message",
 *         example="7b22746167223a2230303030303030303030303030303030222c2263697068657274657874223a226362393966336536227d"
 *     ),
 *     @OA\Property(
 *         property="encryption_key",
 *         type="string",
 *         description="Encrypted session key for decryption",
 *         example="a1b2c3d4e5f6..."
 *     ),
 *     @OA\Property(
 *         property="iv",
 *         type="string",
 *         description="Initialization vector for decryption",
 *         example="a1b2c3d4e5f6a1b2c3d4e5f6a1b2c3d4"
 *     ),
 *     @OA\Property(
 *         property="type",
 *         type="string",
 *         enum={"text", "image", "voice", "video", "file"},
 *         description="Type of the message",
 *         example="text"
 *     ),
 *     @OA\Property(
 *         property="created_at",
 *         type="string",
 *         format="date-time",
 *         description="Message creation timestamp",
 *         example="2023-01-01T12:00:00Z"
 *     ),
 *     @OA\Property(
 *         property="read_at",
 *         type="string",
 *         format="date-time",
 *         description="Message read timestamp",
 *         example="2023-01-01T12:05:00Z"
 *     ),
 *     @OA\Property(
 *         property="sender",
 *         ref="#/components/schemas/User"
 *     ),
 * )
 */
class Message extends Model
{
    public const TYPE_TEXT = 'text';
    public const TYPE_IMAGE = 'image';
    public const TYPE_VOICE = 'voice';
    public const TYPE_VIDEO = 'video';
    public const TYPE_FILE = 'file';

    protected $fillable = [
        'chat_id',
        'sender_id',
        'encrypted_content',
        'encryption_key',
        'iv',
        'type',
    ];

    protected $casts = [
        'chat_id' => 'integer',
        'sender_id' => 'string',
    ];

    /**
     * Отправитель сообщения
     */
    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    /**
     * Чат, к которому принадлежит сообщение
     */
    public function chat(): BelongsTo
    {
        return $this->belongsTo(Chat::class);
    }

    /**
     * Вложения сообщения
     */
    public function attachments(): HasMany
    {
        return $this->hasMany(Attachment::class);
    }

    /**
     * Статусы прочтения
     */
    public function readStatuses(): HasMany
    {
        return $this->hasMany(MessageReadStatus::class);
    }

    /**
     * Проверка, прочитано ли сообщение пользователем
     */
    public function isReadBy(User $user): bool
    {
        return $this->readStatuses()
            ->where('user_id', $user->id)
            ->whereNotNull('read_at')
            ->exists();
    }

    /**
     * Пометить сообщение как прочитанное
     */
    public function markAsReadBy(User $user): void
    {
        $this->readStatuses()->updateOrCreate(
            ['user_id' => $user->id],
            ['read_at' => now()]
        );
    }

    /**
     * Пометить сообщение как доставленное
     */
    public function markAsDeliveredTo(User $user): void
    {
        $this->readStatuses()->updateOrCreate(
            ['user_id' => $user->id],
            ['delivered_at' => now()]
        );
    }

    /**
     * Проверка, является ли сообщение аудио
     */
    public function isVoice(): bool
    {
        return $this->type === self::TYPE_VOICE;
    }

    /**
     * Проверка, есть ли вложения
     */
    public function hasAttachments(): bool
    {
        return $this->attachments()->exists();
    }
}
