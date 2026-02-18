<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use OpenApi\Annotations as OA;

/**
 * @OA\Schema(
 *     schema="Call",
 *     type="object",
 *     title="Call",
 *     description="Модель звонка (аудио/видео)",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="call_uuid", type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440000"),
 *     @OA\Property(property="chat_id", type="integer", example=1),
 *     @OA\Property(property="caller_id", type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440000"),
 *     @OA\Property(property="callee_id", type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440001"),
 *     @OA\Property(property="type", type="string", enum={"audio", "video"}, example="video"),
 *     @OA\Property(property="status", type="string", enum={"pending", "ringing", "active", "ended", "missed", "declined", "failed"}, example="active"),
 *     @OA\Property(property="duration", type="integer", nullable=true, example=120),
 *     @OA\Property(property="started_at", type="string", format="date-time"),
 *     @OA\Property(property="answered_at", type="string", format="date-time"),
 *     @OA\Property(property="ended_at", type="string", format="date-time")
 * )
 */
class Call extends Model
{
    public const TYPE_AUDIO = 'audio';
    public const TYPE_VIDEO = 'video';

    public const STATUS_PENDING = 'pending';
    public const STATUS_RINGING = 'ringing';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_ENDED = 'ended';
    public const STATUS_MISSED = 'missed';
    public const STATUS_DECLINED = 'declined';
    public const STATUS_FAILED = 'failed';

    protected $fillable = [
        'call_uuid',
        'chat_id',
        'caller_id',
        'callee_id',
        'type',
        'status',
        'sdp_offer',
        'sdp_answer',
        'ice_candidates',
        'started_at',
        'answered_at',
        'ended_at',
        'duration',
        'end_reason',
    ];

    protected $casts = [
        'ice_candidates' => 'array',
        'started_at' => 'datetime',
        'answered_at' => 'datetime',
        'ended_at' => 'datetime',
        'duration' => 'integer',
        'caller_id' => 'string',
        'callee_id' => 'string',
    ];

    protected $hidden = [
        'sdp_offer',
        'sdp_answer',
        'ice_candidates',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (Call $call) {
            if (empty($call->call_uuid)) {
                $call->call_uuid = Str::uuid()->toString();
            }
        });
    }

    /**
     * Чат звонка
     */
    public function chat(): BelongsTo
    {
        return $this->belongsTo(Chat::class);
    }

    /**
     * Инициатор звонка
     */
    public function caller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'caller_id');
    }

    /**
     * Получатель звонка
     */
    public function callee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'callee_id');
    }

    /**
     * Является ли звонок видео
     */
    public function isVideo(): bool
    {
        return $this->type === self::TYPE_VIDEO;
    }

    /**
     * Является ли звонок аудио
     */
    public function isAudio(): bool
    {
        return $this->type === self::TYPE_AUDIO;
    }

    /**
     * Активен ли звонок
     */
    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    /**
     * Ожидает ли звонок ответа
     */
    public function isPending(): bool
    {
        return in_array($this->status, [self::STATUS_PENDING, self::STATUS_RINGING], true);
    }

    /**
     * Завершён ли звонок
     */
    public function isEnded(): bool
    {
        return in_array($this->status, [
            self::STATUS_ENDED,
            self::STATUS_MISSED,
            self::STATUS_DECLINED,
            self::STATUS_FAILED,
        ], true);
    }

    /**
     * Принять звонок
     */
    public function answer(string $sdpAnswer): void
    {
        $this->update([
            'status' => self::STATUS_ACTIVE,
            'sdp_answer' => $sdpAnswer,
            'answered_at' => now(),
        ]);
    }

    /**
     * Завершить звонок
     */
    public function end(string $reason = 'normal'): void
    {
        $endedAt = now();
        $duration = null;

        if ($this->answered_at) {
            $duration = $endedAt->diffInSeconds($this->answered_at);
        }

        $this->update([
            'status' => self::STATUS_ENDED,
            'ended_at' => $endedAt,
            'duration' => $duration,
            'end_reason' => $reason,
        ]);
    }

    /**
     * Отклонить звонок
     */
    public function decline(): void
    {
        $this->update([
            'status' => self::STATUS_DECLINED,
            'ended_at' => now(),
            'end_reason' => 'declined',
        ]);
    }

    /**
     * Пометить как пропущенный
     */
    public function markAsMissed(): void
    {
        $this->update([
            'status' => self::STATUS_MISSED,
            'ended_at' => now(),
            'end_reason' => 'missed',
        ]);
    }

    /**
     * Добавить ICE кандидата
     */
    public function addIceCandidate(string $candidate): void
    {
        $candidates = $this->ice_candidates ?? [];
        $candidates[] = $candidate;

        $this->update(['ice_candidates' => $candidates]);
    }
}
