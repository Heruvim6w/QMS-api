<?php

declare(strict_types=1);

namespace App\Swagger;

use OpenApi\Annotations as OA;

/**
 * Swagger documentation for Request DTOs and Query Parameters
 *
 * @OA\Schema(
 *     schema="RegisterRequestBody",
 *     type="object",
 *     title="Register Request",
 *     required={"name", "email", "password", "password_confirmation"},
 *     @OA\Property(
 *         property="name",
 *         type="string",
 *         minLength=2,
 *         maxLength=255,
 *         example="John Doe"
 *     ),
 *     @OA\Property(
 *         property="email",
 *         type="string",
 *         format="email",
 *         example="john@example.com"
 *     ),
 *     @OA\Property(
 *         property="password",
 *         type="string",
 *         format="password",
 *         minLength=8,
 *         pattern="^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)",
 *         example="SecurePass123!"
 *     ),
 *     @OA\Property(
 *         property="password_confirmation",
 *         type="string",
 *         format="password",
 *         example="SecurePass123!"
 *     )
 * )
 *
 * @OA\Schema(
 *     schema="LoginRequestBody",
 *     type="object",
 *     title="Login Request",
 *     required={"login", "password"},
 *     @OA\Property(
 *         property="login",
 *         type="string",
 *         description="Email or UIN (8-digit identifier)",
 *         example="john@example.com"
 *     ),
 *     @OA\Property(
 *         property="password",
 *         type="string",
 *         format="password",
 *         example="SecurePass123!"
 *     ),
 *     @OA\Property(
 *         property="device_name",
 *         type="string",
 *         nullable=true,
 *         example="iPhone 13"
 *     )
 * )
 *
 * @OA\Schema(
 *     schema="ConfirmLoginRequestBody",
 *     type="object",
 *     title="Confirm Login Request",
 *     required={"token"},
 *     @OA\Property(
 *         property="token",
 *         type="string",
 *         format="uuid",
 *         example="550e8400-e29b-41d4-a716-446655440000"
 *     )
 * )
 *
 * @OA\Schema(
 *     schema="SendMessageRequestBody",
 *     type="object",
 *     title="Send Message Request",
 *     required={"content"},
 *     @OA\Property(
 *         property="chat_id",
 *         type="integer",
 *         nullable=true,
 *         example=1
 *     ),
 *     @OA\Property(
 *         property="receiver_id",
 *         type="string",
 *         format="uuid",
 *         nullable=true,
 *         example="550e8400-e29b-41d4-a716-446655440001"
 *     ),
 *     @OA\Property(
 *         property="content",
 *         type="string",
 *         maxLength=65535,
 *         example="Hello! How are you?"
 *     ),
 *     @OA\Property(
 *         property="type",
 *         type="string",
 *         enum={"text", "image", "voice", "video", "file"},
 *         default="text"
 *     )
 * )
 *
 * @OA\Schema(
 *     schema="CreateGroupChatRequestBody",
 *     type="object",
 *     title="Create Group Chat Request",
 *     required={"name", "user_ids"},
 *     @OA\Property(
 *         property="name",
 *         type="string",
 *         minLength=1,
 *         maxLength=255,
 *         example="Project Discussion"
 *     ),
 *     @OA\Property(
 *         property="user_ids",
 *         type="array",
 *         minItems=1,
 *         @OA\Items(type="string", format="uuid"),
 *         example={"550e8400-e29b-41d4-a716-446655440001", "550e8400-e29b-41d4-a716-446655440002"}
 *     )
 * )
 *
 * @OA\Schema(
 *     schema="UpdateChatRequestBody",
 *     type="object",
 *     title="Update Chat Request",
 *     required={"name"},
 *     @OA\Property(
 *         property="name",
 *         type="string",
 *         minLength=1,
 *         maxLength=255,
 *         example="New Chat Name"
 *     )
 * )
 *
 * @OA\Schema(
 *     schema="AddUserToChatRequestBody",
 *     type="object",
 *     title="Add User to Chat Request",
 *     required={"user_id"},
 *     @OA\Property(
 *         property="user_id",
 *         type="string",
 *         format="uuid",
 *         example="550e8400-e29b-41d4-a716-446655440001"
 *     )
 * )
 *
 * @OA\Schema(
 *     schema="SetStatusRequestBody",
 *     type="object",
 *     title="Set Status Request",
 *     required={"online_status"},
 *     @OA\Property(
 *         property="online_status",
 *         type="string",
 *         enum={"online", "chatty", "angry", "depressed", "home", "work", "eating", "away", "unavailable", "busy", "do_not_disturb"},
 *         example="chatty"
 *     ),
 *     @OA\Property(
 *         property="custom_status",
 *         type="string",
 *         maxLength=50,
 *         nullable=true,
 *         example="На встрече 🎯"
 *     )
 * )
 *
 * @OA\Schema(
 *     schema="SetUsernameRequestBody",
 *     type="object",
 *     title="Set Username Request",
 *     required={"username"},
 *     @OA\Property(
 *         property="username",
 *         type="string",
 *         minLength=3,
 *         maxLength=20,
 *         pattern="^[a-zA-Z0-9_-]+$",
 *         example="john_doe"
 *     )
 * )
 *
 * @OA\Schema(
 *     schema="UpdateLocaleRequestBody",
 *     type="object",
 *     title="Update Locale Request",
 *     required={"locale"},
 *     @OA\Property(
 *         property="locale",
 *         type="string",
 *         enum={"en", "ru", "de"},
 *         example="ru"
 *     )
 * )
 *
 * @OA\Schema(
 *     schema="InitiateCallRequestBody",
 *     type="object",
 *     title="Initiate Call Request",
 *     required={"chat_id", "callee_id", "type", "sdp_offer"},
 *     @OA\Property(
 *         property="chat_id",
 *         type="integer",
 *         example=1
 *     ),
 *     @OA\Property(
 *         property="callee_id",
 *         type="string",
 *         format="uuid",
 *         example="550e8400-e29b-41d4-a716-446655440001"
 *     ),
 *     @OA\Property(
 *         property="type",
 *         type="string",
 *         enum={"audio", "video"},
 *         example="video"
 *     ),
 *     @OA\Property(
 *         property="sdp_offer",
 *         type="string",
 *         example="v=0\r\no=- 1234567890 1 IN IP4 127.0.0.1\r\ns=-\r\nt=0 0\r\n..."
 *     )
 * )
 *
 * @OA\Schema(
 *     schema="AnswerCallRequestBody",
 *     type="object",
 *     title="Answer Call Request",
 *     required={"call_uuid", "sdp_answer"},
 *     @OA\Property(
 *         property="call_uuid",
 *         type="string",
 *         format="uuid",
 *         example="550e8400-e29b-41d4-a716-446655440000"
 *     ),
 *     @OA\Property(
 *         property="sdp_answer",
 *         type="string",
 *         example="v=0\r\no=- 1234567890 1 IN IP4 127.0.0.1\r\ns=-\r\nt=0 0\r\n..."
 *     )
 * )
 *
 * @OA\Schema(
 *     schema="AddIceCandidateRequestBody",
 *     type="object",
 *     title="Add ICE Candidate Request",
 *     required={"call_uuid", "candidate"},
 *     @OA\Property(
 *         property="call_uuid",
 *         type="string",
 *         format="uuid",
 *         example="550e8400-e29b-41d4-a716-446655440000"
 *     ),
 *     @OA\Property(
 *         property="candidate",
 *         type="string",
 *         example="candidate:1 1 UDP 2122252543 192.168.1.1 56789 typ host"
 *     )
 * )
 */
class RequestSchemas
{
}

