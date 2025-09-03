<?php

declare(strict_types=1);

namespace App\Http\Requests\Message;

use App\Http\Requests\BaseJsonFormRequest;
use Illuminate\Contracts\Validation\ValidationRule;

class SendRequest extends BaseJsonFormRequest
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
            'receiver_id' => 'required|exists:users,id',
            'content' => 'required|string',
            'type' => 'sometimes|in:text,image,voice,video,file',
        ];
    }
}
