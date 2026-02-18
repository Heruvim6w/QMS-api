<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use OpenApi\Annotations as OA;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Illuminate\Support\Str;

/**
 * @OA\Schema(
 *     schema="User",
 *     type="object",
 *     title="User",
 *     description="User model with encryption keys",
 *     required={"name", "email", "password"},
 *     @OA\Property(
 *         property="id",
 *         type="string",
 *         format="uuid",
 *         description="Unique identifier for the user (UUID)",
 *         example="550e8400-e29b-41d4-a716-446655440000"
 *     ),
 *     @OA\Property(
 *         property="name",
 *         type="string",
 *         description="User's full name",
 *         example="John Doe"
 *     ),
 *     @OA\Property(
 *         property="email",
 *         type="string",
 *         format="email",
 *         description="User's email address",
 *         example="john@example.com"
 *     ),
 *     @OA\Property(
 *         property="uin",
 *         type="string",
 *         description="User's UIN (8-digit unique identifier like in ICQ)",
 *         example="12345678"
 *     ),
 *     @OA\Property(
 *         property="username",
 *         type="string",
 *         description="User's unique username (latin letters, digits, underscore, dash)",
 *         example="john_doe",
 *         nullable=true
 *     ),
 *     @OA\Property(
 *         property="status",
 *         type="string",
 *         enum={"online", "offline"},
 *         description="User status (online/offline)",
 *         example="online"
 *     ),
 *     @OA\Property(
 *         property="online_status",
 *         type="string",
 *         enum={"online", "chatty", "angry", "depressed", "home", "work", "eating", "away", "unavailable", "busy", "do_not_disturb"},
 *         description="User's selected online status",
 *         example="chatty"
 *     ),
 *     @OA\Property(
 *         property="custom_status",
 *         type="string",
 *         description="User's custom status text (max 50 characters, supports emoji)",
 *         example="На встрече 🎯",
 *         nullable=true
 *     ),
 *     @OA\Property(
 *         property="last_seen_at",
 *         type="string",
 *         format="date-time",
 *         description="Last time user was online",
 *         example="2023-01-01T12:00:00Z",
 *         nullable=true
 *     ),
 *     @OA\Property(
 *         property="public_key",
 *         type="string",
 *         description="User's public encryption key",
 *         example="-----BEGIN PUBLIC KEY-----..."
 *     ),
 *     @OA\Property(
 *         property="created_at",
 *         type="string",
 *         format="date-time",
 *         description="User creation timestamp",
 *         example="2023-01-01T12:00:00Z"
 *     ),
 *     @OA\Property(
 *         property="updated_at",
 *         type="string",
 *         format="date-time",
 *         description="User last update timestamp",
 *         example="2023-01-01T12:00:00Z"
 *     )
 * )
 */
class User extends Authenticatable implements JWTSubject
{
    use HasFactory, Notifiable, HasApiTokens;

    /**
     * Константы для статусов пользователя
     */
    public const STATUS_ONLINE = 'online';
    public const STATUS_OFFLINE = 'offline';

    /**
     * Константы для онлайн-статусов
     */
    public const ONLINE_STATUS_ONLINE = 'online';
    public const ONLINE_STATUS_CHATTY = 'chatty';
    public const ONLINE_STATUS_ANGRY = 'angry';
    public const ONLINE_STATUS_DEPRESSED = 'depressed';
    public const ONLINE_STATUS_HOME = 'home';
    public const ONLINE_STATUS_WORK = 'work';
    public const ONLINE_STATUS_EATING = 'eating';
    public const ONLINE_STATUS_AWAY = 'away';
    public const ONLINE_STATUS_UNAVAILABLE = 'unavailable';
    public const ONLINE_STATUS_BUSY = 'busy';
    public const ONLINE_STATUS_DO_NOT_DISTURB = 'do_not_disturb';

    /**
     * Получить все доступные статусы из файла переводов
     * Возвращает массив со статусом как ключ и локализованным названием как значение
     */
    public static function getAvailableStatuses(): array
    {
        return [
            self::ONLINE_STATUS_ONLINE,
            self::ONLINE_STATUS_CHATTY,
            self::ONLINE_STATUS_ANGRY,
            self::ONLINE_STATUS_DEPRESSED,
            self::ONLINE_STATUS_HOME,
            self::ONLINE_STATUS_WORK,
            self::ONLINE_STATUS_EATING,
            self::ONLINE_STATUS_AWAY,
            self::ONLINE_STATUS_UNAVAILABLE,
            self::ONLINE_STATUS_BUSY,
            self::ONLINE_STATUS_DO_NOT_DISTURB,
        ];
    }

    /**
     * The storage format of the model's ID.
     */
    protected $keyType = 'string';

    /**
     * Indicates if the IDs are auto-incrementing.
     */
    public $incrementing = false;

    protected $fillable = [
        'name',
        'email',
        'password',
        'public_key',
        'private_key',
        'uin',
        'username',
        'status',
        'online_status',
        'custom_status',
        'last_seen_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'private_key',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'last_seen_at' => 'datetime',
    ];

    /**
     * Bootstrap the model and its traits.
     */
    protected static function boot(): void
    {
        parent::boot();

        // Автоматически генерируем UUID при создании пользователя
        static::creating(function (User $user) {
            if (empty($user->{$user->getKeyName()})) {
                $user->{$user->getKeyName()} = Str::uuid()->toString();
            }

            // Генерируем UIN если его нет
            if (empty($user->uin)) {
                $user->uin = self::generateUIN();
            }
        });
    }

    /**
     * Генерировать уникальный UIN (как в ICQ)
     * Формат: 8-значное число
     */
    public static function generateUIN(): string
    {
        do {
            $uin = str_pad((string)random_int(10000000, 99999999), 8, '0', STR_PAD_LEFT);
        } while (self::where('uin', $uin)->exists());

        return $uin;
    }

    /**
     * Найти пользователя по UIN или username
     * @param string $identifier - либо UIN (8 цифр), либо username
     */
    public static function findByIdentifier(string $identifier): ?self
    {
        // Если выглядит как UIN (8-значное число)
        if (preg_match('/^\d{8}$/', $identifier)) {
            return self::where('uin', $identifier)->first();
        }

        // Иначе ищем по username
        return self::where('username', $identifier)->first();
    }

    /**
     * Проверить валидность username
     * Только латиница, цифры, подчеркивание, дефис, минимум 3 символа
     */
    public static function isValidUsername(string $username): bool
    {
        return preg_match('/^[a-zA-Z0-9_-]{3,20}$/', $username) === 1;
    }

    /**
     * Получить локализованное имя статуса из файла переводов
     */
    public function getStatusName(): string
    {
        return __("statuses.{$this->online_status}");
    }

    /**
     * Установить онлайн статус (когда пользователь онлайн)
     */
    public function setOnlineStatus(string $onlineStatus, ?string $customStatus = null): void
    {
        // Проверяем, что это валидный статус
        if (!in_array($onlineStatus, self::getAvailableStatuses())) {
            throw new \InvalidArgumentException("Invalid online status: {$onlineStatus}");
        }

        // Проверяем кастомный статус (макс 50 символов)
        if ($customStatus && strlen($customStatus) > 50) {
            throw new \InvalidArgumentException("Custom status cannot exceed 50 characters");
        }

        $this->update([
            'status' => self::STATUS_ONLINE,
            'online_status' => $onlineStatus,
            'custom_status' => $customStatus,
            'last_seen_at' => now(),
        ]);
    }

    /**
     * Установить пользователя онлайн (с дефолтным статусом)
     */
    public function setOnline(): void
    {
        $this->update([
            'status' => self::STATUS_ONLINE,
            'online_status' => self::ONLINE_STATUS_ONLINE,
            'last_seen_at' => now(),
        ]);
    }

    /**
     * Установить пользователя оффлайн (не может быть выбран пользователем)
     * Вызывается автоматически через 3 минуты неактивности
     */
    public function setOffline(): void
    {
        $this->update([
            'status' => self::STATUS_OFFLINE,
            'last_seen_at' => now(),
        ]);
    }

    /**
     * Проверить, онлайн ли пользователь
     */
    public function isOnline(): bool
    {
        return $this->status === self::STATUS_ONLINE;
    }

    /**
     * Получить отображаемый статус (с кастомным текстом если есть)
     */
    public function getDisplayStatus(): string
    {
        if ($this->custom_status) {
            return "{$this->getStatusName()} - {$this->custom_status}";
        }

        return $this->getStatusName();
    }

    /**
     * Получить время, когда пользователь был в сети
     * Используется для отображения друзьям
     */
    public function getLastSeenFormatted(): ?string
    {
        if (!$this->last_seen_at) {
            return null;
        }

        // Если онлайн - показываем "онлайн"
        if ($this->isOnline()) {
            return null; // null означает что пользователь онлайн
        }

        // Если оффлайн - показываем время
        return $this->last_seen_at->diffForHumans();
    }

    /**
     * Сообщения, отправленные пользователем
     */
    public function sentMessages(): HasMany
    {
        return $this->hasMany(Message::class, 'sender_id');
    }

    /**
     * Чаты, в которых участвует пользователь
     */
    public function chats(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Chat::class, 'chat_users')
            ->withPivot(['is_muted', 'joined_at', 'is_active'])
            ->withTimestamps();
    }

    /**
     * Звонки, инициированные пользователем
     */
    public function outgoingCalls(): HasMany
    {
        return $this->hasMany(Call::class, 'caller_id');
    }

    /**
     * Звонки, полученные пользователем
     */
    public function incomingCalls(): HasMany
    {
        return $this->hasMany(Call::class, 'callee_id');
    }

    /**
     * Чат "Избранное" пользователя
     */
    public function favoritesChat(): \Illuminate\Database\Eloquent\Relations\HasOneThrough
    {
        return $this->hasOneThrough(
            Chat::class,
            ChatUser::class,
            'user_id',
            'id',
            'id',
            'chat_id'
        )->where('chats.type', 'favorites');
    }

    public function generateKeyPair(): void
    {
        $config = [
            "digest_alg" => "sha512",
            "private_key_bits" => 4096,
            "private_key_type" => OPENSSL_KEYTYPE_RSA,
        ];

        $keyPair = openssl_pkey_new($config);

        openssl_pkey_export($keyPair, $privateKey);

        $publicKey = openssl_pkey_get_details($keyPair);
        $publicKey = $publicKey["key"];

        $this->public_key = $publicKey;
        $this->private_key = $privateKey;
    }

    /**
     * Get the identifier that will be stored in the subject claim of the JWT.
     *
     * @return mixed
     */
    public function getJWTIdentifier(): mixed
    {
        return $this->getKey();
    }

    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     *
     * @return array
     */
    public function getJWTCustomClaims(): array
    {
        return [];
    }
}
