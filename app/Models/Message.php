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
 *     required={"sender_id", "receiver_id", "encrypted_content", "iv", "type"},
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
 *         type="integer",
 *         format="int64",
 *         description="ID of the user who sent the message",
 *         example=1
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
        'sender_id',
        'receiver_id',
        'encrypted_content',
        'encryption_key',
        'iv',
        'type',
        'file_path',
        'read_at',
    ];

    protected $casts = [
        'read_at' => 'datetime',
    ];

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function chat(): BelongsTo
    {
        return $this->belongsTo(Chat::class);
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(Attachment::class);
    }
}
