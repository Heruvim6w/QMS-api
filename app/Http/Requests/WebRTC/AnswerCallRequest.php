<?php

namespace App\Http\Requests\WebRTC;

use App\Http\Requests\BaseJsonFormRequest;
use Illuminate\Contracts\Validation\ValidationRule;

class AnswerCallRequest extends BaseJsonFormRequest
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
            'call_id' => 'required',
            'sdp_answer' => 'required',
        ];
    }
}
