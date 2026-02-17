<?php

declare(strict_types=1);

namespace App\Http\Requests\WebRTC;

use App\Http\Requests\BaseJsonFormRequest;
use Illuminate\Contracts\Validation\ValidationRule;

class InitiateCallRequest extends BaseJsonFormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'chat_id' => 'required|integer|exists:chats,id',
            'callee_id' => 'required|integer|exists:users,id',
            'type' => 'required|in:audio,video',
            'sdp_offer' => 'required|string',
        ];
    }
}
