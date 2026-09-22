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
            'listing_type' => ['required', 'in:sale,exchange'],
            'book_title' => ['required', 'string', 'max:255'],
            'google_volume_id' => ['nullable', 'string', 'max:120'],
            'book_cover_url' => ['nullable', 'url', 'max:2048'],
            'exchange_book_title' => ['nullable', 'required_if:listing_type,exchange', 'string', 'max:255'],
            'exchange_google_volume_id' => ['nullable', 'string', 'max:120'],
            'exchange_book_cover_url' => ['nullable', 'url', 'max:2048'],
            'exchange_book_author' => ['nullable', 'string', 'max:255'],
            'author' => ['nullable', 'string', 'max:255'],
            'condition' => ['required', 'in:Jauna,Ļoti labs stāvoklis,Labs stāvoklis,Lietota'],
            'language' => ['required', 'string', 'max:50'],
            'price' => ['nullable', 'required_if:listing_type,sale', 'numeric', 'min:0', 'max:999999.99'],
            'description' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
