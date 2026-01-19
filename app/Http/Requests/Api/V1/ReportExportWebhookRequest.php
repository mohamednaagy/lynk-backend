<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class ReportExportWebhookRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Adjust authorization logic as needed
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'media_id' => 'required|exists:media,id',
            'export_type' => 'required|string|max:255',
            'model_id' => 'required|exists:users,id',
        ];
    }

    /**
     * Get custom messages for validation errors.
     */
    public function messages(): array
    {
        return [
            'media_id.required' => 'Media ID is required.',
            'media_id.exists' => 'The selected media ID does not exist.',
            'export_type.required' => 'Export type is required.',
            'export_type.string' => 'Export type must be a string.',
            'export_type.max' => 'Export type may not be greater than 255 characters.',
            'model_id.required' => 'Model ID is required.',
            'model_id.exists' => 'The selected user does not exist.',
        ];
    }
}
