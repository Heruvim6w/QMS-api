<?php

declare(strict_types=1);

namespace App\Http\Requests\User;

use App\Http\Requests\BaseJsonFormRequest;
use Illuminate\Contracts\Validation\ValidationRule;

class SetUsernameRequest extends BaseJsonFormRequest
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
            'username' => 'required|string|regex:/^[a-zA-Z0-9_-]{3,20}$/|unique:users,username',
        ];
    }

    /**
     * Get custom messages for validation errors.
     */
    public function messages(): array
    {
        return [
            'username.required' => 'Username is required',
            'username.regex' => 'Username must contain only latin letters, digits, underscore or dash (3-20 characters)',
            'username.unique' => 'This username is already taken',
        ];
    }
}

