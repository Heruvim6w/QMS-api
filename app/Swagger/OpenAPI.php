<?php

declare(strict_types=1);

namespace App\Swagger;

use OpenApi\Annotations as OA;

/**
 * @OA\OpenApi(
 *     openapi="3.0.0"
 * )
 *
 * @OA\Server(
 *     url="http://localhost:8000",
 *     description="Development Server",
 *     @OA\ServerVariable(
 *         serverVariable="protocol",
 *         default="http",
 *         enum={"http", "https"}
 *     )
 * )
 *
 * @OA\Tag(
 *     name="Authentication",
 *     description="User authentication and registration endpoints",
 *     @OA\ExternalDocumentation(
 *         description="Authentication Flow Documentation",
 *         url="https://github.com/Heruvim6w/QMS-api#authentication"
 *     )
 * )
 *
 * @OA\Tag(
 *     name="Users",
 *     description="User profile management and search",
 *     @OA\ExternalDocumentation(
 *         description="User Management Documentation",
 *         url="https://github.com/Heruvim6w/QMS-api#user-management"
 *     )
 * )
 *
 * @OA\Tag(
 *     name="Chats",
 *     description="Chat management (private, group, favorites)",
 *     @OA\ExternalDocumentation(
 *         description="Chat Management Documentation",
 *         url="https://github.com/Heruvim6w/QMS-api#chat-management"
 *     )
 * )
 *
 * @OA\Tag(
 *     name="Messages",
 *     description="Message sending, receiving, and encryption",
 *     @OA\ExternalDocumentation(
 *         description="Message Documentation",
 *         url="https://github.com/Heruvim6w/QMS-api#messages"
 *     )
 * )
 *
 * @OA\Tag(
 *     name="Attachments",
 *     description="File attachment management and download",
 *     @OA\ExternalDocumentation(
 *         description="File Management Documentation",
 *         url="https://github.com/Heruvim6w/QMS-api#attachments"
 *     )
 * )
 *
 * @OA\Tag(
 *     name="WebRTC",
 *     description="Audio and video call management",
 *     @OA\ExternalDocumentation(
 *         description="WebRTC Calls Documentation",
 *         url="https://github.com/Heruvim6w/QMS-api#webrtc-calls"
 *     )
 * )
 *
 * @OA\Response(
 *     response="401",
 *     description="Unauthenticated. Missing or invalid JWT token"
 * )
 *
 * @OA\Response(
 *     response="403",
 *     description="Access denied. User does not have permission to access this resource"
 * )
 *
 * @OA\Response(
 *     response="404",
 *     description="Resource not found"
 * )
 *
 * @OA\Response(
 *     response="422",
 *     description="Validation error. Invalid request data"
 * )
 *
 * @OA\Response(
 *     response="429",
 *     description="Too many requests. Rate limit exceeded"
 * )
 *
 * @OA\Response(
 *     response="500",
 *     description="Internal server error"
 * )
 *
 * @OA\Schema(
 *     schema="PaginationMeta",
 *     type="object",
 *     @OA\Property(property="current_page", type="integer", example=1),
 *     @OA\Property(property="per_page", type="integer", example=15),
 *     @OA\Property(property="total", type="integer", example=100),
 *     @OA\Property(property="last_page", type="integer", example=7)
 * )
 */
class OpenAPI
{
}

