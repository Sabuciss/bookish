<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CompleteReadingTimerSessionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'pages_read' => ['required', 'integer', 'min:0', 'max:2000'],
            'notes' => ['nullable', 'string', 'max:500'],
            'is_public' => ['required', 'boolean'],
        ];
    }
}
