<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * Pivot модель для связи чатов и пользователей
 */
class ChatUser extends Pivot
{
    protected $table = 'chat_users';

    public $timestamps = false;

    protected $fillable = [
        'chat_id',
        'user_id',
        'is_muted',
        'joined_at',
        'is_active',
    ];

    protected $casts = [
        'is_muted' => 'boolean',
        'is_active' => 'boolean',
        'joined_at' => 'datetime',
    ];

    /**
     * Чат
     */
    public function chat(): BelongsTo
    {
        return $this->belongsTo(Chat::class);
    }

    /**
     * Пользователь
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
