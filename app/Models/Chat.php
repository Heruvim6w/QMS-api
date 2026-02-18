<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use OpenApi\Annotations as OA;

/**
 * @OA\Schema(
 *     schema="Chat",
 *     type="object",
 *     title="Chat",
 *     description="Модель чата (личный, групповой, избранное)",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="type", type="string", enum={"private", "group", "favorites"}, example="private"),
 *     @OA\Property(property="name", type="string", nullable=true, example="Рабочий чат"),
 *     @OA\Property(property="creator_id", type="string", format="uuid", nullable=true, example="550e8400-e29b-41d4-a716-446655440000"),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 */
class Chat extends Model
{
    public const TYPE_PRIVATE = 'private';
    public const TYPE_GROUP = 'group';
    public const TYPE_FAVORITES = 'favorites';

    protected $fillable = [
        'type',
        'name',
        'creator_id',
    ];

    protected $casts = [
        'creator_id' => 'string',
    ];

    /**
     * Участники чата
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'chat_users')
            ->withPivot(['is_muted', 'joined_at', 'is_active'])
            ->withTimestamps();
    }

    /**
     * Активные участники чата
     */
    public function activeUsers(): BelongsToMany
    {
        return $this->users()->wherePivot('is_active', true);
    }

    /**
     * Сообщения чата
     */
    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    /**
     * Создатель чата
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    /**
     * Звонки в чате
     */
    public function calls(): HasMany
    {
        return $this->hasMany(Call::class);
    }

    /**
     * Последнее сообщение
     */
    public function lastMessage(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Message::class)->latestOfMany();
    }

    /**
     * Проверка, является ли чат личным
     */
    public function isPrivate(): bool
    {
        return $this->type === self::TYPE_PRIVATE;
    }

    /**
     * Проверка, является ли чат групповым
     */
    public function isGroup(): bool
    {
        return $this->type === self::TYPE_GROUP;
    }

    /**
     * Проверка, является ли чат избранным
     */
    public function isFavorites(): bool
    {
        return $this->type === self::TYPE_FAVORITES;
    }

    /**
     * Проверка, является ли пользователь участником
     */
    public function hasUser(User $user): bool
    {
        return $this->users()->where('user_id', $user->id)->exists();
    }

    /**
     * Проверка, является ли пользователь создателем
     */
    public function isCreator(User $user): bool
    {
        return $this->creator_id === $user->id;
    }
}
