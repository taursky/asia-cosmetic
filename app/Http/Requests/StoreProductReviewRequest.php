<?php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\File;

class StoreProductReviewRequest extends FormRequest
{
    public function authorize(): bool { return $this->user('web') !== null; }
    public function rules(): array
    {
        return [
            'rating' => ['required', 'integer', 'between:1,5'],
            'body' => ['required', 'string', 'min:10', 'max:5000'],
            'media' => ['sometimes', 'array', 'max:5'],
            'media.*' => ['required', File::types(['jpg','jpeg','png','webp','mp4','webm','mov'])->max(50 * 1024)],
        ];
    }
    public function after(): array
    {
        return [function ($validator) {
            foreach ($this->file('media', []) as $index => $file) {
                if ($file && str_starts_with((string) $file->getMimeType(), 'image/') && $file->getSize() > 10 * 1024 * 1024) {
                    $validator->errors()->add("media.$index", 'Фотография должна быть не больше 10 МБ.');
                }
            }
        }];
    }
}
