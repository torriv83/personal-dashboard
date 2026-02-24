<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\Bpa;

use Illuminate\Foundation\Http\FormRequest;

class StoreShiftRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'assistant_id' => ['required', 'integer', 'exists:assistants,id'],
            'from_date' => ['required', 'date'],
            'from_time' => ['required_if:is_all_day,false', 'nullable', 'date_format:H:i'],
            'to_date' => ['required', 'date', 'after_or_equal:from_date'],
            'to_time' => ['required_if:is_all_day,false', 'nullable', 'date_format:H:i'],
            'is_unavailable' => ['sometimes', 'boolean'],
            'is_all_day' => ['sometimes', 'boolean'],
            'note' => ['sometimes', 'nullable', 'string', 'max:1000'],
            // Recurring fields (only for unavailable entries)
            'is_recurring' => ['sometimes', 'boolean'],
            'recurring_interval' => ['required_if:is_recurring,true', 'nullable', 'in:weekly,biweekly,monthly'],
            'recurring_end_type' => ['required_if:is_recurring,true', 'nullable', 'in:count,date'],
            'recurring_count' => ['required_if:recurring_end_type,count', 'nullable', 'integer', 'min:1', 'max:52'],
            'recurring_end_date' => ['required_if:recurring_end_type,date', 'nullable', 'date', 'after:from_date'],
        ];
    }

    /**
     * Get custom error messages.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'assistant_id.required' => 'Velg en assistent',
            'assistant_id.exists' => 'Ugyldig assistent',
            'from_date.required' => 'Velg startdato',
            'to_date.required' => 'Velg sluttdato',
            'to_date.after_or_equal' => 'Sluttdato må være etter startdato',
            'from_time.required_if' => 'Starttidspunkt er påkrevd',
            'to_time.required_if' => 'Sluttidspunkt er påkrevd',
            'recurring_interval.required_if' => 'Velg gjentagelsesintervall',
            'recurring_end_type.required_if' => 'Velg avslutningstype for gjentagelse',
            'recurring_count.required_if' => 'Angi antall gjentagelser',
            'recurring_end_date.required_if' => 'Angi sluttdato for gjentagelse',
        ];
    }
}
