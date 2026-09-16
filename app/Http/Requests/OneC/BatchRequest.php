<?php

namespace App\Http\Requests\OneC;

use Illuminate\Foundation\Http\FormRequest;

class BatchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'exchange_id' => ['nullable', 'string', 'max:100'],
            'items' => ['required', 'array', 'max:1000'],
            'items.*' => ['required', 'array'],
        ];
    }
}
