<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;
use Illuminate\Http\JsonResponse;

class BaseJsonFormRequest extends FormRequest
{
    protected function failedValidation(Validator $validator): void
    {
        $response = new JsonResponse([
            'message' => 'Validation failed',
            'errors' => $validator->errors()
        ], 422);
        throw new ValidationException($validator, $response);
    }
}
