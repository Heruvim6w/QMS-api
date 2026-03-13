<?php

declare(strict_types=1);

namespace App\Http\Requests\User;

use App\Http\Requests\BaseJsonFormRequest;
use Illuminate\Contracts\Validation\ValidationRule;

class LoginRequest extends BaseJsonFormRequest
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
            // Можно авторизоваться по email или UIN (8-значное число)
            'login' => 'required|string|min:3',
            'password' => 'required|string|min:8|max:25',
        ];
    }

    /**
     * Get custom messages for validation errors.
     */
    public function messages(): array
    {
        return [
            'login.required' => 'Email or UIN is required',
            'login.min' => 'Email or UIN must be at least 3 characters',
            'password.required' => 'Password is required',
            'password.min' => 'Password must be at least 8 characters',
        ];
    }
}
