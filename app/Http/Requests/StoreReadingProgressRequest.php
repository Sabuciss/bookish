<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreReadingProgressRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'entry_id' => ['nullable', 'integer'],
            'challenge_id' => [
                'nullable',
                'integer',
                Rule::exists('reading_challenges', 'id')->where(fn ($query) => $query
                    ->where('user_id', $this->user()?->id)
                    ->where('challenge_type', 'pages')),
            ],
            'book_title' => ['required', 'string', 'max:255'],
            'google_volume_id' => ['nullable', 'string', 'max:120'],
            'book_cover_url' => ['nullable', 'url', 'max:2048'],
            'pages_read' => ['required', 'integer', 'min:0', 'max:100000'],
            'total_pages' => ['nullable', 'integer', 'min:1', 'max:100000'],
            'reading_status' => ['nullable', 'in:want_to_read,in_progress,read'],
            'emotion' => ['required', 'string', 'max:255'],
            'reading_date' => ['required', 'date'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $pagesRead = (int) $this->input('pages_read');
            $totalPages = $this->input('total_pages');

            if ($totalPages !== null && $totalPages !== '' && $pagesRead > (int) $totalPages) {
                $validator->errors()->add('pages_read', 'Izlasīto lapu skaits nevar būt lielāks par kopējo lapu skaitu.');
            }

            if ($this->input('reading_status') === 'read' && ($totalPages === null || $totalPages === '')) {
                $validator->errors()->add('total_pages', 'Lai statuss būtu Read, jānorāda kopējais lapu skaits.');
            }

            $readingDateInput = (string) $this->input('reading_date', '');
            $startTimeInput = (string) $this->input('start_time', '');
            $endTimeInput = (string) $this->input('end_time', '');

            if ($readingDateInput === '' || $startTimeInput === '' || $endTimeInput === '') {
                return;
            }

            try {
                $readingDate = Carbon::parse($readingDateInput);
                $challengeId = $this->input('challenge_id');
                if ($challengeId && ! $validator->errors()->has('challenge_id')) {
                    $challenge = \App\Models\ReadingChallenge::query()
                        ->where('user_id', $this->user()?->id)
                        ->find($challengeId);

                    $challengeDate = $readingDate->toDateString();
                    if ($challenge && ($challengeDate < $challenge->start_date->toDateString()
                        || $challengeDate > $challenge->end_date->toDateString())) {
                        $validator->errors()->add('challenge_id', 'Lasīšanas datumam jābūt izaicinājuma periodā.');
                    }
                }

                $today = now()->startOfDay();

                if ($readingDate->greaterThan($today)) {
                    $validator->errors()->add('reading_date', 'Nevar pievienot reading progress ar nākotnes datumu.');
                    return;
                }

                if ($readingDate->isSameDay($today)) {
                    $now = now();
                    $startAt = Carbon::createFromFormat('Y-m-d H:i', $readingDate->toDateString() . ' ' . $startTimeInput);
                    $endAt = Carbon::createFromFormat('Y-m-d H:i', $readingDate->toDateString() . ' ' . $endTimeInput);

                    if ($startAt->greaterThan($now)) {
                        $validator->errors()->add('start_time', 'Sākuma laiks nevar būt nākotnē.');
                    }

                    if ($endAt->greaterThan($now)) {
                        $validator->errors()->add('end_time', 'Beigu laiks nevar būt nākotnē.');
                    }
                }

                $startAt = Carbon::createFromFormat('Y-m-d H:i', $readingDate->toDateString() . ' ' . $startTimeInput);
                $endAt = Carbon::createFromFormat('Y-m-d H:i', $readingDate->toDateString() . ' ' . $endTimeInput);
                if ($startAt->diffInMinutes($endAt) > 720) {
                    $validator->errors()->add('end_time', 'Vienas lasīšanas sesijas ilgums nevar pārsniegt 12 stundas.');
                }
            } catch (\Throwable $exception) {
                // Date/time format validation is handled by the base rules.
            }
        });
    }
}
