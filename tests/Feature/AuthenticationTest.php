<?php

namespace Tests\Feature;

use App\Models\LoginToken;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test 1: Регистрация - проверка, что пользователь не может залогиниться без подтверждения почты
     * (В этой системе при регистрации сразу выдается JWT токен, но нужно проверить логику)
     */
    public function test_user_can_register_and_receive_jwt_token(): void
    {
        $response = $this->postJson('/api/v1/register', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'SecurePass123!',
            'password_confirmation' => 'SecurePass123!',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'user' => ['id', 'name', 'email', 'uin', 'username'],
                'access_token',
                'token_type',
                'expires_in'
            ]);

        $this->assertDatabaseHas('users', [
            'email' => 'john@example.com',
            'name' => 'John Doe',
        ]);
    }

    /**
     * Test 2: Регистрация сохраняет в БД всю необходимую информацию и присваивает UIN
     */
    public function test_user_registration_stores_all_required_data_and_generates_uin(): void
    {
        $this->postJson('/api/v1/register', [
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'password' => 'SecurePass123!',
            'password_confirmation' => 'SecurePass123!',
        ]);

        $user = User::where('email', 'jane@example.com')->first();

        $this->assertNotNull($user);
        $this->assertEquals('Jane Doe', $user->name);
        $this->assertNotNull($user->uin);
        $this->assertTrue(strlen($user->uin) === 8);
        $this->assertTrue(ctype_digit($user->uin));
        $this->assertNotNull($user->public_key);
        $this->assertNull($user->username); // username должен быть NULL по умолчанию
    }

    /**
     * Test 3: Регистрация с невалидным email
     */
    public function test_registration_fails_with_invalid_email(): void
    {
        $response = $this->postJson('/api/v1/register', [
            'name' => 'John Doe',
            'email' => 'invalid-email',
            'password' => 'SecurePass123!',
            'password_confirmation' => 'SecurePass123!',
        ]);

        $response->assertStatus(422);
    }

    /**
     * Test 4: Регистрация с коротким паролем
     */
    public function test_registration_fails_with_short_password(): void
    {
        $response = $this->postJson('/api/v1/register', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'Short1!',
            'password_confirmation' => 'Short1!',
        ]);

        $response->assertStatus(422);
    }

    /**
     * Test 5: Регистрация с несовпадающими паролями
     */
    public function test_registration_fails_with_mismatched_passwords(): void
    {
        $response = $this->postJson('/api/v1/register', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'SecurePass123!',
            'password_confirmation' => 'DifferentPass123!',
        ]);

        $response->assertStatus(422);
    }

    /**
     * Test 6: Регистрация с дублирующимся email
     */
    public function test_registration_fails_with_duplicate_email(): void
    {
        User::factory()->create(['email' => 'john@example.com']);

        $response = $this->postJson('/api/v1/register', [
            'name' => 'Another John',
            'email' => 'john@example.com',
            'password' => 'SecurePass123!',
            'password_confirmation' => 'SecurePass123!',
        ]);

        $response->assertStatus(422);
    }

    /**
     * Test 7: При входе с нового устройства отправляется письмо с подтверждением
     */
    public function test_login_from_new_device_sends_confirmation_email(): void
    {
        Mail::fake();

        $user = User::factory()->create([
            'email' => 'john@example.com',
            'password' => bcrypt('SecurePass123!'),
        ]);

        $response = $this->postJson('/api/v1/login', [
            'login' => 'john@example.com',
            'password' => 'SecurePass123!',
            'device_name' => 'iPhone 13',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'requires_confirmation' => true,
                'message' => 'Confirmation email sent. Link is valid for 3 hours.',
            ]);

        Mail::assertSent(\App\Mail\LoginConfirmationMail::class);

        $this->assertDatabaseHas('login_tokens', [
            'user_id' => $user->id,
            'device_name' => 'iPhone 13',
            'is_confirmed' => false,
        ]);
    }

    /**
     * Test 8: После подтверждения по почте пользователь получает JWT токен
     */
    public function test_user_can_confirm_login_and_receive_jwt_token(): void
    {
        $user = User::factory()->create([
            'email' => 'john@example.com',
            'password' => bcrypt('SecurePass123!'),
        ]);

        $loginToken = LoginToken::create([
            'user_id' => $user->id,
            'token' => LoginToken::generateToken(),
            'device_name' => 'iPhone 13',
            'is_confirmed' => false,
            'expires_at' => now()->addHours(3),
        ]);

        $response = $this->postJson('/api/v1/login/confirm', [
            'token' => $loginToken->token,
        ]);


        $response->assertStatus(200)
            ->assertJsonStructure([
                'access_token',
                'token_type',
                'expires_in',
                'user' => ['id', 'name', 'email', 'uin'],
            ]);

        $loginToken->refresh();
        $this->assertTrue($loginToken->is_confirmed);
    }

    /**
     * Test 9: Подтверждение с неверным токеном возвращает ошибку
     */
    public function test_confirm_login_with_invalid_token_fails(): void
    {
        $response = $this->postJson('/api/v1/login/confirm', [
            'token' => 'invalid-token-that-does-not-exist',
        ]);

        $response->assertStatus(401);
    }

    /**
     * Test 10: Истекший токен не может быть использован
     */
    public function test_expired_login_token_cannot_be_used(): void
    {
        $user = User::factory()->create();

        LoginToken::create([
            'user_id' => $user->id,
            'token' => 'expired-token',
            'device_name' => 'iPhone 13',
            'is_confirmed' => false,
            'expires_at' => now()->subHour(),
        ]);

        $response = $this->postJson('/api/v1/login/confirm', [
            'token' => 'expired-token',
        ]);

        $response->assertStatus(401);
    }

    /**
     * Test 11: Известное устройство получает JWT токен сразу без подтверждения
     */
    public function test_login_from_known_device_returns_token_immediately(): void
    {
        Mail::fake();

        $user = User::factory()->create([
            'email' => 'john@example.com',
            'password' => bcrypt('SecurePass123!'),
        ]);

        // Первый логин - создаем подтвержденную сессию
        $userAgent = 'Mozilla/5.0 (iPhone; CPU iPhone OS 14_0 like Mac OS X)';
        $ipAddress = '192.168.1.1';

        LoginToken::create([
            'user_id' => $user->id,
            'token' => 'known-device-token',
            'user_agent' => $userAgent,
            'ip_address' => $ipAddress,
            'device_name' => 'iPhone 13',
            'is_confirmed' => true,
            'expires_at' => now()->addHours(30),
        ]);

        // Второй логин с тем же девайсом
        $response = $this->postJson('/api/v1/login', [
            'login' => 'john@example.com',
            'password' => 'SecurePass123!',
        ], [
            'User-Agent' => $userAgent,
            'REMOTE_ADDR' => $ipAddress, // Устанавливаем REMOTE_ADDR вместо X-Forwarded-For
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'requires_confirmation' => false,
            ])
            ->assertJsonStructure(['access_token', 'token_type']);
    }

    /**
     * Test 12: Активные сессии продолжают работать при новом логине
     */
    public function test_existing_sessions_remain_active_after_new_login(): void
    {
        $user = User::factory()->create([
            'email' => 'john@example.com',
            'password' => bcrypt('SecurePass123!'),
        ]);

        // Создаем активную сессию
        $activeSession = LoginToken::create([
            'user_id' => $user->id,
            'token' => 'active-session-token',
            'device_name' => 'Desktop',
            'is_confirmed' => true,
            'expires_at' => now()->addHours(2),
        ]);

        // Логинимся с нового устройства
        $this->postJson('/api/v1/login', [
            'login' => 'john@example.com',
            'password' => 'SecurePass123!',
            'device_name' => 'Mobile',
        ]);

        // Проверяем, что старая сессия остается активной
        $activeSession->refresh();
        $this->assertTrue($activeSession->is_confirmed);
        $this->assertGreaterThan(now(), $activeSession->expires_at);
    }

    /**
     * Test 13: Логин с неверными учетными данными
     */
    public function test_login_with_invalid_credentials(): void
    {
        User::factory()->create([
            'email' => 'john@example.com',
            'password' => bcrypt('SecurePass123!'),
        ]);

        $response = $this->postJson('/api/v1/login', [
            'login' => 'john@example.com',
            'password' => 'WrongPassword123!',
        ]);

        $response->assertStatus(401)
            ->assertJson(['error' => 'Invalid credentials']);
    }

    /**
     * Test 14: Логин с UIN вместо email
     */
    public function test_login_with_uin_instead_of_email(): void
    {
        Mail::fake();

        $user = User::factory()->create([
            'email' => 'john@example.com',
            'password' => bcrypt('SecurePass123!'),
        ]);

        $response = $this->postJson('/api/v1/login', [
            'login' => $user->uin,
            'password' => 'SecurePass123!',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['requires_confirmation', 'message']);
    }

    /**
     * Test 15: Логаут удаляет сессию
     */
    public function test_user_can_logout(): void
    {
        $user = User::factory()->create();

        $token = \Tymon\JWTAuth\Facades\JWTAuth::fromUser($user);

        $response = $this->withHeaders([
            'Authorization' => "Bearer $token",
        ])->postJson('/api/v1/logout');

        $response->assertStatus(200);
    }
}

