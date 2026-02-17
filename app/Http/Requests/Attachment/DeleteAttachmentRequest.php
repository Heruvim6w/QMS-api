<?php

declare(strict_types=1);

namespace App\Http\Requests\Attachment;

use App\Http\Requests\BaseJsonFormRequest;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Request для удаления вложения
 *
 * Параметры валидируются в контроллере (attachment ID из URL),
 * в теле запроса нет данных, требующих валидации.
 */
class DeleteAttachmentRequest extends BaseJsonFormRequest
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
     * ID вложения валидируется в маршруте через findOrFail,
     * других параметров для этого запроса не требуется.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [];
    }
}

