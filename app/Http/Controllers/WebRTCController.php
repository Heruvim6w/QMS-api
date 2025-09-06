<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\WebRTC\AddIceCandidateRequest;
use App\Http\Requests\WebRTC\AnswerCallRequest;
use App\Http\Requests\WebRTC\InitiateCallRequest;
use OpenApi\Annotations as OA;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * @OA\Tag(
 *     name="WebRTC",
 *     description="API Endpoints for WebRTC Calls"
 * )
 */
class WebRTCController extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/v1/calls/initiate",
     *     summary="Initiate a WebRTC call",
     *     tags={"WebRTC"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"receiver_id"},
     *             @OA\Property(property="receiver_id", type="integer", example=2),
     *             @OA\Property(property="is_video", type="boolean", example=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Call initiated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="call_id", type="string", example="abc123def456"),
     *             @OA\Property(property="sdp_offer", type="string", example="v=0\r\no=- 1234567890 1 IN IP4 127.0.0.1\r\n..."),
     *             @OA\Property(
     *                 property="ice_candidates",
     *                 type="array",
     *                 @OA\Items(type="string", example="candidate:1 1 UDP 2122252543 192.168.1.1 56789 typ host")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="The given data was invalid."),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     )
     * )
     */
    public function initiateCall(InitiateCallRequest $request): JsonResponse
    {
        return response()->json([
            'call_id' => uniqid('', true),
            'sdp_offer' => 'generated_sdp_offer',
            'ice_candidates' => []
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/v1/calls/answer",
     *     summary="Answer a WebRTC call",
     *     tags={"WebRTC"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"call_id", "sdp_answer"},
     *             @OA\Property(property="call_id", type="string", example="abc123def456"),
     *             @OA\Property(property="sdp_answer", type="string", example="v=0\r\no=- 1234567890 1 IN IP4 127.0.0.1\r\n...")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Call answered successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="call_answered")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="The given data was invalid."),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     )
     * )
     */
    public function answerCall(AnswerCallRequest $request): JsonResponse
    {
        return response()->json(['status' => 'call_answered']);
    }

    /**
     * @OA\Post(
     *     path="/api/v1/calls/ice-candidate",
     *     summary="Add ICE candidate for WebRTC call",
     *     tags={"WebRTC"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"call_id", "candidate"},
     *             @OA\Property(property="call_id", type="string", example="abc123def456"),
     *             @OA\Property(property="candidate", type="string", example="candidate:1 1 UDP 2122252543 192.168.1.1 56789 typ host")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="ICE candidate added successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="candidate_added")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="The given data was invalid."),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     )
     * )
     */
    public function addIceCandidate(AddIceCandidateRequest $request): JsonResponse
    {
        return response()->json(['status' => 'candidate_added']);
    }
}
