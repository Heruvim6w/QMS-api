<?php

declare(strict_types=1);

namespace App\Http\Requests\User;

use App\Http\Requests\BaseJsonFormRequest;
use Illuminate\Contracts\Validation\ValidationRule;

class ConfirmLoginRequest extends BaseJsonFormRequest
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
     * Убираем строгую проверку размера токена, пусть контроллер обрабатывает несуществующие или истекшие токены.
     * Это позволяет возвращать 401 для неподходящих токенов, как ожидают тесты.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // Раньше: 'token' => 'required|string|size:64',
            'token' => 'required|string',
        ];
    }

    /**
     * Get custom messages for validation errors.
     */
    public function messages(): array
    {
        return [
            'token.required' => 'Confirmation token is required',
            'token.size' => 'Invalid token format',
        ];
    }
}
