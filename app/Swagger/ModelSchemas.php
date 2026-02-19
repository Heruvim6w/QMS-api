<?php

declare(strict_types=1);

namespace App\Swagger;

use OpenApi\Annotations as OA;

/**
 * Swagger documentation for User model and related schemas
 *
 *
 *
 * @OA\Schema(
 *     schema="UserPublicProfile",
 *     type="object",
 *     title="User Public Profile",
 *     description="Public user information visible to other users",
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
 *     schema="LoginToken",
 *     type="object",
 *     title="Login Token",
 *     description="Temporary token for email confirmation (valid for 3 hours)",
 *     @OA\Property(
 *         property="id",
 *         type="integer",
 *         description="Token ID"
 *     ),
 *     @OA\Property(
 *         property="user_id",
 *         type="string",
 *         format="uuid",
 *         description="User UUID"
 *     ),
 *     @OA\Property(
 *         property="token",
 *         type="string",
 *         format="uuid",
 *         description="Unique confirmation token sent in email"
 *     ),
 *     @OA\Property(
 *         property="device_name",
 *         type="string",
 *         description="User-provided device name"
 *     ),
 *     @OA\Property(
 *         property="ip_address",
 *         type="string",
 *         format="ipv4"
 *     ),
 *     @OA\Property(
 *         property="user_agent",
 *         type="string",
 *         description="Device user agent string"
 *     ),
 *     @OA\Property(
 *         property="confirmed_at",
 *         type="string",
 *         format="date-time",
 *         nullable=true,
 *         description="When token was confirmed"
 *     ),
 *     @OA\Property(
 *         property="expires_at",
 *         type="string",
 *         format="date-time",
 *         description="Token expiration time (3 hours after creation)"
 *     ),
 *     @OA\Property(
 *         property="created_at",
 *         type="string",
 *         format="date-time"
 *     )
 * )
 */
class ModelSchemas
{
}

