<?php

declare(strict_types=1);

namespace App\Swagger;

use OpenApi\Annotations as OA;

/**
 * Swagger API Usage Examples and Path Documentation
 *
 * @OA\PathItem(
 *     path="/api/v1",
 *     description="QMS Messenger API v1 root"
 * )
 *
 * @OA\ExternalDocumentation(
 *     description="QMS API GitHub Repository",
 *     url="https://github.com/Heruvim6w/QMS-api"
 * )
 *
 * Authentication Flow Example:
 * 1. Register: POST /api/v1/register -> Get JWT token
 * 2. Or Login Step 1: POST /api/v1/login -> New device gets confirmation email or known device gets JWT
 * 3. Or Login Step 2: POST /api/v1/login/confirm -> Verify email and get JWT
 * 4. Use JWT in Bearer token for all subsequent requests
 * 5. When token expires, use POST /api/v1/refresh to get a new one
 * 6. Logout: POST /api/v1/logout to invalidate token
 *
 * Chat Types:
 * - private: One-to-one chat between two users (created automatically on first message)
 * - group: Multiple users, has a name, managed by creator
 * - favorites: Special chat for marking important messages/chats
 *
 * Message Encryption:
 * - All messages are end-to-end encrypted
 * - Content is encrypted with user's RSA public key
 * - Only recipient with private key can decrypt
 * - Server stores only encrypted data
 *
 * User Identification:
 * - UUID (id): Internal identifier for database queries
 * - UIN (uin): 8-digit unique identifier shown in contact list (assigned at registration)
 * - Username: Optional custom username for easier lookup (can be changed anytime)
 * - Email: Login credential
 *
 * User Status System:
 * - status: "online" or "offline" (automatic based on 3-minute inactivity)
 * - online_status: Mood selection when online (chatty, angry, etc.)
 * - custom_status: Optional text (max 50 chars with emoji)
 * - If offline, shows last_seen_at timestamp
 *
 * Session Management:
 * - Each device gets separate session after email confirmation
 * - GET /api/v1/sessions shows all active devices
 * - DELETE /api/v1/sessions/{id} logs out from specific device
 * - Other devices remain logged in
 *
 * WebRTC Calling:
 * - Initiator sends SDP offer (initiateCall)
 * - Recipient sends SDP answer (answerCall)
 * - Both sides exchange ICE candidates (addIceCandidate)
 * - Call established peer-to-peer without server relay
 *
 * Localization:
 * - Interface language: en, ru, de (selected automatically from system or manually via PUT /api/v1/users/locale)
 * - Status names translated to user's locale
 * - Timestamps in ISO 8601 format (timezone-aware)
 */
class ApiDocumentation
{
}

