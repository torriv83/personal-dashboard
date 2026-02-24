<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\Bpa;

use Illuminate\Foundation\Http\FormRequest;

class ResizeShiftRequest extends FormRequest
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
            'duration_minutes' => ['required', 'integer', 'min:15'],
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
            'duration_minutes.required' => 'Varighet er påkrevd',
            'duration_minutes.integer' => 'Varighet må være et heltall',
            'duration_minutes.min' => 'Minimum varighet er 15 minutter',
        ];
    }
}
