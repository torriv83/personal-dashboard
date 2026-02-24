<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\Bpa;

use Illuminate\Foundation\Http\FormRequest;

class MoveShiftRequest extends FormRequest
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
            'new_date' => ['required', 'date'],
            'new_time' => ['sometimes', 'nullable', 'date_format:H:i'],
            'scope' => ['sometimes', 'in:single,future,all'],
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
            'new_date.required' => 'Ny dato er påkrevd',
            'new_date.date' => 'Ugyldig datoformat',
            'new_time.date_format' => 'Ugyldig tidsformat (bruk HH:MM)',
        ];
    }
}
