<?php
declare(strict_types=1);
namespace App\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;
class UpdateUserLocaleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }
    public function rules(): array
    {
        return [
            'locale' => ['required', 'string', 'in:en,ru,de'],
        ];
    }
    public function messages(): array
    {
        return [
            'locale.required' => 'Language code is required',
            'locale.in' => 'Language must be one of: en, ru, de',
        ];
    }
}
