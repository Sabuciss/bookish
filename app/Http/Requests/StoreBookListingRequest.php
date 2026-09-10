<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreBookListingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'book_title' => ['required', 'string', 'max:255'],
            'author' => ['nullable', 'string', 'max:255'],
            'condition' => ['required', 'in:Jauna,Ļoti labs stāvoklis,Labs stāvoklis,Lietota'],
            'language' => ['required', 'string', 'max:50'],
            'price' => ['required', 'numeric', 'min:0', 'max:999999.99'],
            'description' => ['nullable', 'string', 'max:2000'],
            'contact_email' => ['required', 'email', 'max:255'],
        ];
    }
}
