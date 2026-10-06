<?php

namespace App\Http\Requests\OneC;

use Illuminate\Foundation\Http\FormRequest;

class ImageSyncRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'entity_type' => ['required', 'in:product,variant'],
            'entity_ref' => ['required', 'uuid'],
            'image_ref' => ['required', 'string', 'max:255'],
            'filename' => ['nullable', 'string', 'max:255'],
            'mime_type' => ['nullable', 'string', 'max:100'],
            'position' => ['nullable', 'integer', 'min:1', 'max:10000'],
            'is_primary' => ['nullable', 'boolean'],
            'content_base64' => ['required', 'string'],
        ];
    }
}
