<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreReadingChallengeSessionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'challenge_id' => [
                'nullable',
                'integer',
                Rule::exists('reading_challenges', 'id')->where(fn ($query) => $query->where('user_id', $this->user()?->id)),
            ],
            'planned_minutes' => ['required', 'integer', 'min:1', 'max:1440'],
            'elapsed_seconds' => ['nullable', 'integer', 'min:0'],
            'pages_read' => ['required', 'integer', 'min:0'],
            'notes' => ['nullable', 'string', 'max:500'],
            'started_at' => ['nullable', 'date'],
            'ended_at' => ['nullable', 'date', 'after_or_equal:started_at'],
        ];
    }
}
