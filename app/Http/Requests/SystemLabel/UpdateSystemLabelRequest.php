<?php

namespace App\Http\Requests\SystemLabel;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSystemLabelRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'value' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'value.max' => 'Label text may not exceed 2000 characters.',
        ];
    }
}
