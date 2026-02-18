<?php

declare(strict_types=1);

namespace App\Http\Requests\User;

use App\Http\Requests\BaseJsonFormRequest;
use Illuminate\Contracts\Validation\ValidationRule;

class SetStatusRequest extends BaseJsonFormRequest
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
            'online_status' => 'required|in:online,chatty,angry,depressed,home,work,eating,away,unavailable,busy,do_not_disturb',
            'custom_status' => 'nullable|string|max:50',
        ];
    }

    /**
     * Get custom messages for validation errors.
     */
    public function messages(): array
    {
        return [
            'online_status.required' => 'Online status is required',
            'online_status.in' => 'Invalid online status',
            'custom_status.max' => 'Custom status cannot exceed 50 characters',
        ];
    }
}

