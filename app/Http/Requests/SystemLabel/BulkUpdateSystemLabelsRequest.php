<?php

namespace App\Http\Requests\SystemLabel;

use Illuminate\Foundation\Http\FormRequest;

class BulkUpdateSystemLabelsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'labels' => ['required', 'array', 'min:1'],
            'labels.*.key' => ['required', 'string', 'exists:system_labels,key'],
            'labels.*.value' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'labels.required' => 'At least one label must be provided.',
            'labels.*.key.exists' => 'One or more label keys do not exist.',
            'labels.*.value.max' => 'Label text may not exceed 2000 characters.',
        ];
    }
}
