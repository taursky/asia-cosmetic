<?php

namespace App\Http\Requests\OneC;

use Illuminate\Foundation\Http\FormRequest;

class OrderStatusRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'one_c_id' => ['nullable', 'uuid'],
            'one_c_number' => ['nullable', 'string', 'max:100'],
            'status' => ['required', 'string', 'max:50'],
            'payment_status' => ['nullable', 'string', 'max:50'],
            'comment' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
