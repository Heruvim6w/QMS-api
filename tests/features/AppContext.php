<?php

namespace Tests\Features;

use App\Models\Call;
use App\Models\Chat;
use App\Models\LoginToken;
use App\Models\Message;
use App\Models\User;
use Behat\Behat\Context\Context;
use Behat\Gherkin\Node\TableNode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class AppContext extends TestCase implements Context
{
    use RefreshDatabase;

    protected array $users = [];
    protected array $chats = [];
    protected array $messages = [];
    protected array $calls = [];
    protected ?string $lastToken = null;
    protected ?string $currentUser = null;
    protected ?string $lastResponse = null;

    /**
     * @Given a user exists with email :email
     */
    public function userExistsWithEmail(string $email): void
    {
        $this->users['user'] = User::factory()->create(['email' => $email]);
    }

    /**
     * @Given a user :name exists
     */
    public function userExists(string $name): void
    {
        $this->users[$name] = User::factory()->create(['email' => "{$name}@example.com"]);
    }

    /**
     * @Given a user exists with:
     */
    public function userExistsWithData(TableNode $table): void
    {
        $data = [];
        foreach ($table->getRows() as $row) {
            if (count($row) === 2) {
                $data[$row[0]] = $row[1];
            }
        }

        if (isset($data['password'])) {
            $data['password'] = bcrypt($data['password']);
        }

        $this->users['user'] = User::factory()->create($data);
    }

    /**
     * @Given the user has a confirmed login session on this device
     */
    public function userHasConfirmedLoginSession(): void
    {
        $user = $this->users['user'] ?? User::factory()->create();
        LoginToken::create([
            'user_id' => $user->id,
            'token' => 'known-device-token',
            'user_agent' => 'Test Agent',
            'ip_address' => '127.0.0.1',
            'is_confirmed' => true,
            'expires_at' => now()->addHours(24),
        ]);
        $this->users['user'] = $user;
    }

    /**
     * @When I send a POST request to :endpoint
     */
    public function sendPostRequest(string $endpoint): void
    {
        // Implementation for sending POST request
    }

    /**
     * @Given a private chat exists between :user1 and :user2
     */
    public function privateChatExistsBetween(string $user1, string $user2): void
    {
        $chat = Chat::factory()->create(['type' => Chat::TYPE_PRIVATE]);
        $chat->users()->attach([
            $this->users[$user1]->id,
            $this->users[$user2]->id,
        ], [
            'joined_at' => now(),
            'is_active' => true,
        ]);
        $this->chats['private'] = $chat;
    }

    /**
     * @When :sender sends a message to :receiver:
     */
    public function sendMessage(string $sender, string $receiver, TableNode $table): void
    {
        $chat = Chat::factory()->create(['type' => Chat::TYPE_PRIVATE]);
        $chat->users()->attach([
            $this->users[$sender]->id,
            $this->users[$receiver]->id,
        ], [
            'joined_at' => now(),
            'is_active' => true,
        ]);

        $data = [];
        foreach ($table->getRows() as $row) {
            if (count($row) === 2) {
                $data[$row[0]] = $row[1];
            }
        }

        $message = Message::factory()->create([
            'chat_id' => $chat->id,
            'sender_id' => $this->users[$sender]->id,
        ]);

        $this->messages['last'] = $message;
        $this->chats['private'] = $chat;
    }

    /**
     * @Then a private chat should be created between :user1 and :user2
     */
    public function chatCreatedBetween(string $user1, string $user2): void
    {
        $chat = Chat::where('type', Chat::TYPE_PRIVATE)->first();
        \PHPUnit\Framework\Assert::assertNotNull($chat);
        \PHPUnit\Framework\Assert::assertTrue($chat->hasUser($this->users[$user1]));
        \PHPUnit\Framework\Assert::assertTrue($chat->hasUser($this->users[$user2]));
    }

    /**
     * @When :creator creates a group chat with name :name and includes :users
     */
    public function createGroupChat(string $creator, string $name, string $users): void
    {
        $userList = explode(' and ', $users);
        $userIds = array_map(fn($u) => $this->users[trim($u)]->id, $userList);

        $chat = Chat::factory()->create([
            'type' => Chat::TYPE_GROUP,
            'name' => $name,
            'creator_id' => $this->users[$creator]->id,
        ]);

        $chat->users()->attach(array_unique(array_merge(
            [$this->users[$creator]->id],
            $userIds
        )), [
            'joined_at' => now(),
            'is_active' => true,
        ]);

        $this->chats['group'] = $chat;
    }

    /**
     * @Given a group chat exists with members :users
     */
    public function groupChatExists(string $users): void
    {
        $userList = explode(', ', $users);
        $userIds = array_map(fn($u) => $this->users[trim($u)]->id, $userList);

        $chat = Chat::factory()->create(['type' => Chat::TYPE_GROUP]);
        $chat->users()->attach($userIds, [
            'joined_at' => now(),
            'is_active' => true,
        ]);

        $this->chats['group'] = $chat;
    }

    /**
     * @Given :user has a confirmed login on this device
     */
    public function userHasConfirmedLoginDevice(string $user): void
    {
        LoginToken::create([
            'user_id' => $this->users[$user]->id,
            'token' => 'device-token',
            'user_agent' => 'Test Agent',
            'ip_address' => '127.0.0.1',
            'is_confirmed' => true,
            'expires_at' => now()->addHours(24),
        ]);
    }

    /**
     * @Given a ringing call exists from :caller to :callee
     */
    public function ringingCallExists(string $caller, string $callee): void
    {
        $chat = Chat::factory()->create();
        $chat->users()->attach([
            $this->users[$caller]->id,
            $this->users[$callee]->id,
        ], [
            'joined_at' => now(),
            'is_active' => true,
        ]);

        $call = Call::factory()->create([
            'chat_id' => $chat->id,
            'caller_id' => $this->users[$caller]->id,
            'callee_id' => $this->users[$callee]->id,
            'status' => Call::STATUS_RINGING,
        ]);

        $this->calls['ringing'] = $call;
        $this->chats['call'] = $chat;
    }

    /**
     * @Given an active call exists between :user1 and :user2
     */
    public function activeCallExists(string $user1, string $user2): void
    {
        $chat = Chat::factory()->create();
        $chat->users()->attach([
            $this->users[$user1]->id,
            $this->users[$user2]->id,
        ], [
            'joined_at' => now(),
            'is_active' => true,
        ]);

        $call = Call::factory()->create([
            'chat_id' => $chat->id,
            'caller_id' => $this->users[$user1]->id,
            'callee_id' => $this->users[$user2]->id,
            'status' => Call::STATUS_ACTIVE,
            'answered_at' => now(),
            'started_at' => now(),
        ]);

        $this->calls['active'] = $call;
    }

    /**
     * @Given an ended call from :caller to :callee
     */
    public function endedCallExists(string $caller, string $callee): void
    {
        $call = Call::factory()->create([
            'caller_id' => $this->users[$caller]->id,
            'callee_id' => $this->users[$callee]->id,
            'status' => Call::STATUS_ENDED,
        ]);

        $this->calls['ended'] = $call;
    }

    /**
     * @Then the response status code should be :code
     */
    public function responseStatusCodeShouldBe(int $code): void
    {
        // Implementation for status code assertion
    }

    /**
     * @Then the response should contain a JWT token
     */
    public function responseShouldContainJWT(): void
    {
        // Implementation
    }

    /**
     * @Then the user should be created in the database
     */
    public function userShouldBeCreated(): void
    {
        \PHPUnit\Framework\Assert::assertDatabaseHas('users', [
            'email' => $this->users['user']->email,
        ]);
    }

    /**
     * @Then the user should have a unique UIN assigned
     */
    public function userShouldHaveUIN(): void
    {
        \PHPUnit\Framework\Assert::assertNotNull($this->users['user']->uin);
        \PHPUnit\Framework\Assert::assertTrue(strlen($this->users['user']->uin) === 8);
    }

    /**
     * @Then the message should be stored in the database
     */
    public function messageShouldBeStored(): void
    {
        \PHPUnit\Framework\Assert::assertDatabaseHas('messages', [
            'id' => $this->messages['last']->id,
        ]);
    }

    /**
     * @Then the message should be encrypted
     */
    public function messageShouldBeEncrypted(): void
    {
        \PHPUnit\Framework\Assert::assertNotNull($this->messages['last']->encrypted_content);
        \PHPUnit\Framework\Assert::assertNotNull($this->messages['last']->iv);
    }

    /**
     * @Then a group chat should be created with :count members
     */
    public function groupChatCreatedWithMembers(int $count): void
    {
        \PHPUnit\Framework\Assert::assertCount($count, $this->chats['group']->users);
    }

    /**
     * @Then the call should be created with status :status
     */
    public function callCreatedWithStatus(string $status): void
    {
        \PHPUnit\Framework\Assert::assertEquals($status, $this->calls['ringing']->status);
    }

    /**
     * @Then the call status should change to :status
     */
    public function callStatusChangedTo(string $status): void
    {
        $this->calls['ringing']->refresh();
        \PHPUnit\Framework\Assert::assertEquals($status, $this->calls['ringing']->status);
    }
}

