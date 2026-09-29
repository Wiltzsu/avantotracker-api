<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAvantoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'date' => 'sometimes|date',
            'location' => 'nullable|string|max:255',
            'water_temperature' => 'nullable|numeric|min:0|max:50',
            'duration_minutes' => 'nullable|integer|min:0|max:300',
            'duration_seconds' => 'nullable|integer|min:0|max:59',
            'swear_words' => 'nullable|integer|min:0',
            'feeling_before' => 'nullable|integer|min:1|max:10',
            'feeling_after' => 'nullable|integer|min:1|max:10',
            'sauna' => 'nullable|boolean',
            'sauna_duration' => 'nullable|integer|min:1|max:120',
        ];
    }
}
