<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\WebRTC\AddIceCandidateRequest;
use App\Http\Requests\WebRTC\AnswerCallRequest;
use App\Http\Requests\WebRTC\InitiateCallRequest;
use App\Models\Call;
use App\Models\Chat;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use OpenApi\Annotations as OA;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @OA\Tag(
 *     name="WebRTC",
 *     description="WebRTC audio and video call management using SDP and ICE candidates for peer-to-peer communication"
 * )
 */
class WebRTCController extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/v1/calls/initiate",
     *     operationId="initiateCall",
     *     summary="Initiate a WebRTC call",
     *     description="Start a new audio or video call to another chat member. Both caller and callee must be members of the specified chat. Call enters 'ringing' state and awaits answer. SDP offer contains codec and connection information.",
     *     tags={"WebRTC"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         description="Call initiation parameters",
     *         @OA\JsonContent(
     *             required={"chat_id", "callee_id", "type", "sdp_offer"},
     *             @OA\Property(property="chat_id", type="integer", example=1, description="Chat ID where call is initiated"),
     *             @OA\Property(property="callee_id", type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440001", description="UUID of user to call"),
     *             @OA\Property(property="type", type="string", enum={"audio", "video"}, example="video", description="Call type"),
     *             @OA\Property(property="sdp_offer", type="string", example="v=0\r\no=- 1234567890 1 IN IP4 127.0.0.1\r\ns=-\r\nt=0 0\r\n...", description="SDP offer from caller's WebRTC peer connection")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Call initiated successfully",
     *         @OA\JsonContent(ref="#/components/schemas/CallResponse")
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Access denied - caller or callee not in this chat"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Chat or callee not found"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error"
     *     )
     * )
     */
    public function initiateCall(InitiateCallRequest $request): JsonResponse
    {
        /** @var User $caller */
        $caller = Auth::user();

        if (!$caller) {
            throw new AccessDeniedHttpException();
        }

        $data = $request->validated();

        // Проверяем, что чат существует
        $chat = Chat::findOrFail($data['chat_id']);

        // Проверяем, что оба пользователя участники чата
        if (!$chat->hasUser($caller)) {
            throw new AccessDeniedHttpException('Вы не являетесь участником этого чата');
        }

        $callee = User::findOrFail($data['callee_id']);
        if (!$chat->hasUser($callee)) {
            throw new AccessDeniedHttpException('Получатель не является участником этого чата');
        }

        // Создаем новый звонок
        $call = Call::query()->create([
            'chat_id' => $data['chat_id'],
            'caller_id' => $caller->id,
            'callee_id' => $callee->id,
            'type' => $data['type'],
            'status' => Call::STATUS_RINGING,
            'sdp_offer' => $data['sdp_offer'],
            'started_at' => now(),
        ]);

        return response()->json([
            'call_uuid' => $call->call_uuid,
            'chat_id' => $call->chat_id,
            'caller_id' => $call->caller_id,
            'callee_id' => $call->callee_id,
            'type' => $call->type,
            'status' => $call->status,
        ], Response::HTTP_CREATED);
    }

    /**
     * @OA\Post(
     *     path="/api/v1/calls/answer",
     *     operationId="answerCall",
     *     summary="Answer a WebRTC call",
     *     description="Answer an incoming call. Only the callee can answer their call. Call must be in 'ringing' state. SDP answer contains the callee's codec and connection information.",
     *     tags={"WebRTC"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         description="Call answer parameters",
     *         @OA\JsonContent(
     *             required={"call_uuid", "sdp_answer"},
     *             @OA\Property(property="call_uuid", type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440000", description="UUID of the call to answer"),
     *             @OA\Property(property="sdp_answer", type="string", example="v=0\r\no=- 1234567890 1 IN IP4 127.0.0.1\r\ns=-\r\nt=0 0\r\n...", description="SDP answer from callee's WebRTC peer connection")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Call answered successfully, call becomes active",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="active"),
     *             @OA\Property(property="call_uuid", type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440000")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Access denied - only callee can answer"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Call not found"
     *     ),
     *     @OA\Response(
     *         response=409,
     *         description="Call is not in ringing state (already answered, missed, or declined)"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error"
     *     )
     * )
     */
    public function answerCall(AnswerCallRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();

        if (!$user) {
            throw new AccessDeniedHttpException();
        }

        $data = $request->validated();

        $call = Call::where('call_uuid', $data['call_uuid'])->firstOrFail();

        // Проверяем, что пользователь - получатель звонка
        if ($call->callee_id !== $user->id) {
            throw new AccessDeniedHttpException('Вы не являетесь получателем этого звонка');
        }

        // Проверяем, что звонок еще в состоянии ожидания
        if (!$call->isPending()) {
            return response()->json(
                ['error' => 'Звонок уже завершен или активен'],
                Response::HTTP_CONFLICT
            );
        }

        // Принимаем звонок
        $call->answer($data['sdp_answer']);

        return response()->json([
            'status' => $call->status,
            'call_uuid' => $call->call_uuid,
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/v1/calls/ice-candidate",
     *     operationId="addIceCandidate",
     *     summary="Add ICE candidate for WebRTC call",
     *     description="Add an ICE candidate discovered by the local WebRTC peer connection. Multiple candidates are typically sent for connection optimization. Both caller and callee can add ICE candidates.",
     *     tags={"WebRTC"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         description="ICE candidate information",
     *         @OA\JsonContent(
     *             required={"call_uuid", "candidate"},
     *             @OA\Property(property="call_uuid", type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440000", description="UUID of the call"),
     *             @OA\Property(property="candidate", type="string", example="candidate:1 1 UDP 2122252543 192.168.1.1 56789 typ host", description="ICE candidate string from RTCIceCandidate")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="ICE candidate added successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="candidate_added"),
     *             @OA\Property(property="call_uuid", type="string", format="uuid")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Access denied - not a participant in this call"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Call not found"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error"
     *     )
     * )
     */
    public function addIceCandidate(AddIceCandidateRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();

        if (!$user) {
            throw new AccessDeniedHttpException();
        }

        $data = $request->validated();

        $call = Call::where('call_uuid', $data['call_uuid'])->firstOrFail();

        // Проверяем, что пользователь участник звонка
        if ($call->caller_id !== $user->id && $call->callee_id !== $user->id) {
            throw new AccessDeniedHttpException('Вы не являетесь участником этого звонка');
        }

        // Добавляем ICE кандидата
        $call->addIceCandidate($data['candidate']);

        return response()->json([
            'status' => 'candidate_added',
            'call_uuid' => $call->call_uuid,
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/v1/calls/{callUuid}/decline",
     *     summary="Decline a WebRTC call",
     *     tags={"WebRTC"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="callUuid",
     *         in="path",
     *         required=true,
     *         description="Call UUID",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Call declined",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="declined")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Access denied"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Call not found"
     *     )
     * )
     */
    public function declineCall(string $callUuid): JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();

        if (!$user) {
            throw new AccessDeniedHttpException();
        }

        $call = Call::where('call_uuid', $callUuid)->firstOrFail();

        // Только получатель может отклонить звонок
        if ($call->callee_id !== $user->id) {
            throw new AccessDeniedHttpException('Только получатель может отклонить звонок');
        }

        $call->decline();

        return response()->json(['status' => 'declined']);
    }

    /**
     * @OA\Post(
     *     path="/api/v1/calls/{callUuid}/end",
     *     summary="End a WebRTC call",
     *     tags={"WebRTC"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="callUuid",
     *         in="path",
     *         required=true,
     *         description="Call UUID",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\RequestBody(
     *         @OA\JsonContent(
     *             @OA\Property(property="reason", type="string", example="normal", nullable=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Call ended",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="ended"),
     *             @OA\Property(property="duration", type="integer", example=120)
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Access denied"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Call not found"
     *     )
     * )
     */
    public function endCall(string $callUuid): JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();

        if (!$user) {
            throw new AccessDeniedHttpException();
        }

        $call = Call::where('call_uuid', $callUuid)->firstOrFail();

        // Только участники звонка могут его завершить
        if ($call->caller_id !== $user->id && $call->callee_id !== $user->id) {
            throw new AccessDeniedHttpException('Вы не являетесь участником этого звонка');
        }

        $reason = request()->input('reason', 'normal');
        $call->end($reason);

        return response()->json([
            'status' => $call->status,
            'duration' => $call->duration,
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/calls/{callUuid}",
     *     summary="Get call details",
     *     tags={"WebRTC"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="callUuid",
     *         in="path",
     *         required=true,
     *         description="Call UUID",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Call details",
     *         @OA\JsonContent(ref="#/components/schemas/Call")
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Access denied"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Call not found"
     *     )
     * )
     */
    public function getCall(string $callUuid): JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();

        if (!$user) {
            throw new AccessDeniedHttpException();
        }

        $call = Call::where('call_uuid', $callUuid)
            ->with(['chat', 'caller', 'callee'])
            ->firstOrFail();

        // Только участники звонка могут видеть его детали
        if ($call->caller_id !== $user->id && $call->callee_id !== $user->id) {
            throw new AccessDeniedHttpException('Вы не являетесь участником этого звонка');
        }

        return response()->json($call);
    }
}
