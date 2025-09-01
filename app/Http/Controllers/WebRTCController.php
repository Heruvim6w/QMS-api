<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\WebRTC\AddIceCandidateRequest;
use App\Http\Requests\WebRTC\AnswerCallRequest;
use App\Http\Requests\WebRTC\InitiateCallRequest;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\JsonResponse;

class WebRTCController extends Controller
{
    public function initiateCall(InitiateCallRequest $request): JsonResponse
    {
        return response()->json([
            'call_id' => uniqid('', true),
            'sdp_offer' => 'generated_sdp_offer',
            'ice_candidates' => []
        ]);
    }

    public function answerCall(AnswerCallRequest $request): JsonResponse
    {
        return response()->json(['status' => 'call_answered']);
    }

    public function addIceCandidate(AddIceCandidateRequest $request): JsonResponse
    {
        return response()->json(['status' => 'candidate_added']);
    }
}
