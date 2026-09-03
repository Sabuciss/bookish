<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreReadingHighlightRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'book_title' => ['nullable', 'string', 'max:255'],
            'character' => ['nullable', 'string', 'max:255'],
            'quote_text' => ['required', 'string', 'max:5000'],
            'is_public' => ['nullable', 'boolean'],
        ];
    }
}