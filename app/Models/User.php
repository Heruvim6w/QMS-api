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
 *     ),
 *     @OA\Property(
 *         property="sent_messages",
 *         type="array",
 *         description="Messages sent by the user",
 *         @OA\Items(ref="#/components/schemas/Message")
 *     ),
 *     @OA\Property(
 *         property="received_messages",
 *         type="array",
 *         description="Messages received by the user",
 *         @OA\Items(ref="#/components/schemas/Message")
 *     )
 * )
 */
class User extends Authenticatable implements JWTSubject
{
    use HasFactory, Notifiable, HasApiTokens;

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
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'private_key',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
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
        });
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
