<?php

namespace App\Http\Resources;

use OpenApi\Annotations as OA;

/**
 * @OA\Schema(
 *     schema="Error",
 *     type="object",
 *     title="Error",
 *     description="Error response",
 *     @OA\Property(
 *         property="error",
 *         type="string",
 *         description="Error message",
 *         example="Something went wrong"
 *     )
 * )
 *
 * @OA\Schema(
 *     schema="ValidationError",
 *     type="object",
 *     title="ValidationError",
 *     description="Validation error response",
 *     @OA\Property(
 *         property="message",
 *         type="string",
 *         description="Error message",
 *         example="The given data was invalid."
 *     ),
 *     @OA\Property(
 *         property="errors",
 *         type="object",
 *         description="Validation errors",
 *         example={
 *             "email": {"The email field is required."},
 *             "password": {"The password field is required."}
 *         }
 *     )
 * )
 *
 * @OA\Schema(
 *     schema="Success",
 *     type="object",
 *     title="Success",
 *     description="Success response",
 *     @OA\Property(
 *         property="message",
 *         type="string",
 *         description="Success message",
 *         example="Operation completed successfully"
 *     )
 * )
 */
class SwaggerSchemas
{
    // Этот класс служит только для организации схем Swagger
}
