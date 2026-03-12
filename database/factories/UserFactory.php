<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // Генерируем RSA ключевую пару для E2E шифрования
        $config = [
            "digest_alg" => "sha512",
            "private_key_bits" => 4096,
            "private_key_type" => OPENSSL_KEYTYPE_RSA,
        ];

        $keyPair = openssl_pkey_new($config);
        openssl_pkey_export($keyPair, $privateKey);

        $publicKey = openssl_pkey_get_details($keyPair);
        $publicKey = $publicKey["key"];

        return [
            'id' => Str::uuid()->toString(),
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            'public_key' => $publicKey,
            'private_key' => $privateKey,
            'uin' => str_pad((string)fake()->unique()->numberBetween(10000000, 99999999), 8, '0', STR_PAD_LEFT),
            'username' => null, // По умолчанию username пустой
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn(array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    /**
     * Переопределяем create() чтобы при коллизии UIN делать retry,
     * как в User::createWithUniqueUin().
     */
    public function create($attributes = [], ?\Illuminate\Database\Eloquent\Model $parent = null): mixed
    {
        $maxAttempts = 20;

        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            try {
                return parent::create($attributes, $parent);
            } catch (QueryException $e) {
                $sqlState = $e->errorInfo[0] ?? null;
                $isUinConflict = $sqlState === '23505'
                    && str_contains($e->getMessage(), 'users_uin_unique');

                if ($isUinConflict && empty($attributes['uin'])) {
                    // Сбрасываем кэш уникальных значений faker для uin
                    fake()->unique(true);
                    continue;
                }

                throw $e;
            }
        }

        throw new \RuntimeException('UserFactory: не удалось создать пользователя с уникальным UIN за максимальное число попыток.');
    }
}
