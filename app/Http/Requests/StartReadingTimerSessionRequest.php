<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StartReadingTimerSessionRequest extends FormRequest
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
                Rule::exists('reading_challenges', 'id')
                    ->where(fn ($query) => $query
                        ->where('user_id', $this->user()?->id)
                        ->where('challenge_type', 'time')
                        ->whereDate('start_date', '<=', now()->toDateString())
                        ->whereDate('end_date', '>=', now()->toDateString())),
            ],
            'planned_minutes' => ['required', 'integer', 'min:1', 'max:1440'],
        ];
    }
}
