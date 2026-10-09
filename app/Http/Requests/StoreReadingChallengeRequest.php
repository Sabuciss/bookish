<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Validator;

class StoreReadingChallengeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'challenge_type' => ['required', 'in:pages,time'],
            'target_value' => ['required', 'integer', 'min:1', 'max:1000000'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'notes' => ['nullable', 'string', 'max:500'],
            'mark_as_not_completed' => ['sometimes', 'boolean'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if (! $this->boolean('mark_as_not_completed')) {
                return;
            }

            $endDate = Carbon::parse($this->input('end_date'));
            if (! $endDate->lt(today())) {
                $validator->errors()->add('mark_as_not_completed', 'Izaicinājumu kā neizpildītu var atzīmēt tikai pēc termiņa beigām.');
            }

        });
    }
}
