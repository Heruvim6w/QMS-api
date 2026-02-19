<?php

declare(strict_types=1);

namespace App\Swagger;

use OpenApi\Annotations as OA;

/**
 * HTTP Status Codes and Common Responses
 *
 * @OA\Response(
 *     response="200_OK",
 *     description="Success. Request completed successfully.",
 *     @OA\JsonContent(ref="#/components/schemas/StatusResponse")
 * )
 *
 * @OA\Response(
 *     response="201_Created",
 *     description="Created. Resource successfully created.",
 *     @OA\JsonContent(
 *         @OA\Property(property="id", type="integer", example=1),
 *         @OA\Property(property="status", type="string", example="created")
 *     )
 * )
 *
 * @OA\Response(
 *     response="400_BadRequest",
 *     description="Bad Request. Invalid request syntax or parameters.",
 *     @OA\JsonContent(
 *         @OA\Property(property="error", type="string", example="Invalid request parameters")
 *     )
 * )
 *
 * @OA\Response(
 *     response="401_Unauthorized",
 *     description="Unauthorized. Missing or invalid JWT token. User must login first.",
 *     @OA\JsonContent(
 *         @OA\Property(property="message", type="string", example="Unauthenticated.")
 *     )
 * )
 *
 * @OA\Response(
 *     response="403_Forbidden",
 *     description="Forbidden. User authenticated but lacks permission for this resource.",
 *     @OA\JsonContent(
 *         @OA\Property(property="error", type="string", example="Access denied")
 *     )
 * )
 *
 * @OA\Response(
 *     response="404_NotFound",
 *     description="Not Found. Resource does not exist.",
 *     @OA\JsonContent(
 *         @OA\Property(property="error", type="string", example="Resource not found")
 *     )
 * )
 *
 * @OA\Response(
 *     response="409_Conflict",
 *     description="Conflict. Request conflicts with current resource state.",
 *     @OA\JsonContent(
 *         @OA\Property(property="error", type="string", example="Resource state conflict")
 *     )
 * )
 *
 * @OA\Response(
 *     response="413_PayloadTooLarge",
 *     description="Payload Too Large. File or request body exceeds maximum size.",
 *     @OA\JsonContent(
 *         @OA\Property(property="error", type="string", example="File exceeds maximum size")
 *     )
 * )
 *
 * @OA\Response(
 *     response="422_UnprocessableEntity",
 *     description="Unprocessable Entity. Validation failed for request data.",
 *     @OA\JsonContent(ref="#/components/schemas/ValidationError")
 * )
 *
 * @OA\Response(
 *     response="429_TooManyRequests",
 *     description="Too Many Requests. Rate limit exceeded. Wait before retrying.",
 *     @OA\JsonContent(
 *         @OA\Property(property="error", type="string", example="Rate limit exceeded"),
 *         @OA\Property(property="retry_after", type="integer", example=60, description="Seconds to wait")
 *     )
 * )
 *
 * @OA\Response(
 *     response="500_InternalServerError",
 *     description="Internal Server Error. Unexpected server error.",
 *     @OA\JsonContent(
 *         @OA\Property(property="error", type="string", example="Internal server error")
 *     )
 * )
 *
 * Usage Notes:
 *
 * 1. AUTHENTICATION
 *    - Get JWT token via /register or /login endpoints
 *    - Include token in Authorization header: "Authorization: Bearer <token>"
 *    - Tokens expire after 1 hour (configurable in config/jwt.php)
 *    - Use /refresh to get a new token before expiration
 *    - Use /logout to invalidate token immediately
 *
 * 2. IDENTIFIERS
 *    - UUID (id): Primary key, 36-char format like 550e8400-e29b-41d4-a716-446655440000
 *    - UIN: 8-digit number assigned at registration (e.g., 12345678)
 *    - Username: 3-20 character string, latin only (e.g., john_doe)
 *    - Use any of these in search endpoints, but UUID required for direct operations
 *
 * 3. ENCRYPTION
 *    - All message content encrypted end-to-end with RSA keys
 *    - User public key generated automatically at registration
 *    - Only recipient with private key can decrypt
 *    - Server cannot read message content
 *
 * 4. MESSAGE STATUS
 *    - sent: Message created in database
 *    - delivered: Received at least one member's client
 *    - read: Member marked message as read
 *
 * 5. CHAT TYPES
 *    - private: 1-to-1 chat, created automatically
 *    - group: Multi-user chat with name, created explicitly
 *    - favorites: Special personal collection
 *
 * 6. ERROR HANDLING
 *    - Check HTTP status code first
 *    - JSON response includes "error" or "message" field
 *    - Validation errors include "errors" object with field names as keys
 *    - Always log errors for debugging
 *
 * 7. PAGINATION
 *    - Use limit/offset or page-based pagination where applicable
 *    - Default limit: 50 items
 *    - Maximum limit: 100 items
 *
 * 8. TIMESTAMPS
 *    - All timestamps in ISO 8601 format: "2026-02-19T10:30:00Z"
 *    - Always UTC timezone
 *    - Parse as datetime objects, not strings
 *
 * 9. RATE LIMITING
 *    - 60 requests per minute per IP for non-authenticated
 *    - 300 requests per minute per user for authenticated
 *    - Check X-RateLimit-* headers in response
 *
 * 10. BEST PRACTICES
 *     - Always check response status before processing data
 *     - Implement exponential backoff for 429 responses
 *     - Cache user profiles and status data
 *     - Use WebSocket for real-time message updates (if available)
 *     - Validate file types before upload
 *     - Handle token refresh transparently on 401 response
 */
class HttpResponses
{
}

