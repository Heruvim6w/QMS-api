<?php

declare(strict_types=1);

namespace App\Swagger;

use OpenApi\Annotations as OA;

/**
 * @OA\Schema(
 *     schema="RegisterResponse",
 *     type="object",
 *     title="Register Response",
 *     description="Successful user registration response",
 *     @OA\Property(
 *         property="user",
 *         type="object",
 *         @OA\Property(property="id", type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440000"),
 *         @OA\Property(property="name", type="string", example="John Doe"),
 *         @OA\Property(property="email", type="string", format="email", example="john@example.com"),
 *         @OA\Property(property="uin", type="string", example="12345678", description="8-digit UIN generated at registration"),
 *         @OA\Property(property="username", type="string", nullable=true, example=null)
 *     ),
 *     @OA\Property(property="access_token", type="string", description="JWT access token"),
 *     @OA\Property(property="token_type", type="string", example="bearer"),
 *     @OA\Property(property="expires_in", type="integer", description="Token TTL in seconds", example=3600)
 * )
 *
 * @OA\Schema(
 *     schema="LoginStep1Response",
 *     type="object",
 *     title="Login Step 1 Response - New Device",
 *     description="Response when login is from a new device (requires email confirmation)",
 *     @OA\Property(
 *         property="message",
 *         type="string",
 *         example="Confirmation email sent. Link is valid for 3 hours."
 *     ),
 *     @OA\Property(
 *         property="requires_confirmation",
 *         type="boolean",
 *         example=true
 *     )
 * )
 *
 * @OA\Schema(
 *     schema="LoginStep2Response",
 *     type="object",
 *     title="Login Step 2 Response - Known Device",
 *     description="Immediate login response for known devices",
 *     @OA\Property(property="access_token", type="string", description="JWT access token"),
 *     @OA\Property(property="token_type", type="string", example="bearer"),
 *     @OA\Property(property="expires_in", type="integer", example=3600),
 *     @OA\Property(
 *         property="user",
 *         type="object",
 *         @OA\Property(property="id", type="string", format="uuid"),
 *         @OA\Property(property="name", type="string"),
 *         @OA\Property(property="email", type="string", format="email"),
 *         @OA\Property(property="uin", type="string"),
 *         @OA\Property(property="username", type="string", nullable=true)
 *     ),
 *     @OA\Property(
 *         property="requires_confirmation",
 *         type="boolean",
 *         example=false
 *     )
 * )
 *
 * @OA\Schema(
 *     schema="ConfirmLoginResponse",
 *     type="object",
 *     title="Confirm Login Response",
 *     description="Response after email confirmation",
 *     @OA\Property(property="access_token", type="string"),
 *     @OA\Property(property="token_type", type="string", example="bearer"),
 *     @OA\Property(property="expires_in", type="integer", example=3600)
 * )
 *
 * @OA\Schema(
 *     schema="UserProfileResponse",
 *     type="object",
 *     title="User Profile",
 *     description="Complete user profile information",
 *     @OA\Property(property="id", type="string", format="uuid"),
 *     @OA\Property(property="name", type="string"),
 *     @OA\Property(property="email", type="string", format="email"),
 *     @OA\Property(property="uin", type="string", description="8-digit unique identifier"),
 *     @OA\Property(property="username", type="string", nullable=true, description="Custom username (3-20 chars, latin only)"),
 *     @OA\Property(property="status", type="string", enum={"online", "offline"}, description="Current online status"),
 *     @OA\Property(property="online_status", type="string", enum={"online", "chatty", "angry", "depressed", "home", "work", "eating", "away", "unavailable", "busy", "do_not_disturb"}, description="Selected status mood"),
 *     @OA\Property(property="custom_status", type="string", nullable=true, maxLength=50, description="User custom status text with emoji support"),
 *     @OA\Property(property="last_seen_at", type="string", format="date-time", nullable=true, description="Last online timestamp"),
 *     @OA\Property(property="locale", type="string", example="ru", description="User interface language"),
 *     @OA\Property(property="created_at", type="string", format="date-time")
 * )
 *
 * @OA\Schema(
 *     schema="SearchUserResponse",
 *     type="object",
 *     title="Search User Result",
 *     description="User search result by UIN or username",
 *     @OA\Property(property="id", type="string", format="uuid"),
 *     @OA\Property(property="name", type="string"),
 *     @OA\Property(property="uin", type="string"),
 *     @OA\Property(property="username", type="string", nullable=true),
 *     @OA\Property(property="status", type="string", enum={"online", "offline"}),
 *     @OA\Property(property="online_status", type="string"),
 *     @OA\Property(property="custom_status", type="string", nullable=true),
 *     @OA\Property(property="last_seen_at", type="string", format="date-time", nullable=true)
 * )
 *
 * @OA\Schema(
 *     schema="ChatResponse",
 *     type="object",
 *     title="Chat",
 *     description="Chat object response",
 *     @OA\Property(property="id", type="integer"),
 *     @OA\Property(property="type", type="string", enum={"private", "group", "favorites"}),
 *     @OA\Property(property="name", type="string", nullable=true, description="Group chat name or null for private"),
 *     @OA\Property(property="creator_id", type="string", format="uuid", nullable=true),
 *     @OA\Property(
 *         property="users",
 *         type="array",
 *         @OA\Items(ref="#/components/schemas/UserProfileResponse")
 *     ),
 *     @OA\Property(
 *         property="last_message",
 *         ref="#/components/schemas/MessageResponse",
 *         nullable=true
 *     ),
 *     @OA\Property(property="unread_count", type="integer", description="Unread messages count"),
 *     @OA\Property(property="is_muted", type="boolean"),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 *
 * @OA\Schema(
 *     schema="MessageResponse",
 *     type="object",
 *     title="Message",
 *     description="Decrypted message object",
 *     @OA\Property(property="id", type="integer"),
 *     @OA\Property(property="chat_id", type="integer"),
 *     @OA\Property(
 *         property="sender",
 *         type="object",
 *         @OA\Property(property="id", type="string", format="uuid"),
 *         @OA\Property(property="name", type="string"),
 *         @OA\Property(property="uin", type="string"),
 *         @OA\Property(property="username", type="string", nullable=true)
 *     ),
 *     @OA\Property(property="content", type="string", description="Decrypted message text"),
 *     @OA\Property(property="type", type="string", enum={"text", "image", "voice", "video", "file"}),
 *     @OA\Property(
 *         property="attachments",
 *         type="array",
 *         @OA\Items(ref="#/components/schemas/AttachmentResponse")
 *     ),
 *     @OA\Property(property="is_read", type="boolean"),
 *     @OA\Property(property="created_at", type="string", format="date-time")
 * )
 *
 * @OA\Schema(
 *     schema="AttachmentResponse",
 *     type="object",
 *     title="Attachment",
 *     description="File attachment",
 *     @OA\Property(property="id", type="integer"),
 *     @OA\Property(property="message_id", type="integer"),
 *     @OA\Property(property="file_path", type="string"),
 *     @OA\Property(property="mime_type", type="string", example="image/jpeg"),
 *     @OA\Property(property="size", type="integer", description="File size in bytes"),
 *     @OA\Property(property="name", type="string", description="Original filename"),
 *     @OA\Property(property="created_at", type="string", format="date-time")
 * )
 *
 * @OA\Schema(
 *     schema="CallResponse",
 *     type="object",
 *     title="Call",
 *     description="WebRTC call object",
 *     @OA\Property(property="call_uuid", type="string", format="uuid"),
 *     @OA\Property(property="chat_id", type="integer"),
 *     @OA\Property(
 *         property="caller",
 *         type="object",
 *         @OA\Property(property="id", type="string", format="uuid"),
 *         @OA\Property(property="name", type="string")
 *     ),
 *     @OA\Property(
 *         property="callee",
 *         type="object",
 *         @OA\Property(property="id", type="string", format="uuid"),
 *         @OA\Property(property="name", type="string")
 *     ),
 *     @OA\Property(property="type", type="string", enum={"audio", "video"}),
 *     @OA\Property(property="status", type="string", enum={"pending", "ringing", "active", "ended", "missed", "declined", "failed"}),
 *     @OA\Property(property="duration", type="integer", nullable=true, description="Call duration in seconds"),
 *     @OA\Property(property="started_at", type="string", format="date-time"),
 *     @OA\Property(property="answered_at", type="string", format="date-time", nullable=true),
 *     @OA\Property(property="ended_at", type="string", format="date-time", nullable=true)
 * )
 *
 * @OA\Schema(
 *     schema="StatusResponse",
 *     type="object",
 *     title="Generic Status Response",
 *     @OA\Property(property="status", type="string", example="success"),
 *     @OA\Property(property="message", type="string", nullable=true)
 * )
 *
 * @OA\Schema(
 *     schema="MessageResponse_v2",
 *     type="object",
 *     title="Message Response v2",
 *     description="Complete message response with metadata",
 *     @OA\Property(property="id", type="integer"),
 *     @OA\Property(property="chat_id", type="integer"),
 *     @OA\Property(property="sender", ref="#/components/schemas/UserProfileResponse"),
 *     @OA\Property(property="content", type="string"),
 *     @OA\Property(property="type", type="string", enum={"text", "image", "voice", "video", "file"}),
 *     @OA\Property(
 *         property="attachments",
 *         type="array",
 *         @OA\Items(ref="#/components/schemas/AttachmentResponse")
 *     ),
 *     @OA\Property(
 *         property="read_by",
 *         type="array",
 *         description="List of users who read this message",
 *         @OA\Items(
 *             type="object",
 *             @OA\Property(property="id", type="string", format="uuid"),
 *             @OA\Property(property="name", type="string"),
 *             @OA\Property(property="read_at", type="string", format="date-time")
 *         )
 *     ),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 */
class Schemas
{
}

