<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Статус прочтения сообщения пользователем
 */
class MessageReadStatus extends Model
{
    protected $table = 'message_read_status';

    protected $fillable = [
        'message_id',
        'user_id',
        'read_at',
        'delivered_at',
    ];

    protected $casts = [
        'read_at' => 'datetime',
        'delivered_at' => 'datetime',
    ];

    /**
     * Сообщение
     */
    public function message(): BelongsTo
    {
        return $this->belongsTo(Message::class);
    }

    /**
     * Пользователь
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Прочитано ли сообщение
     */
    public function isRead(): bool
    {
        return $this->read_at !== null;
    }

    /**
     * Доставлено ли сообщение
     */
    public function isDelivered(): bool
    {
        return $this->delivered_at !== null;
    }
}
