<?php

namespace App\Http\Requests\V1\Admin\Companies\LenderClients;

use Illuminate\Foundation\Http\FormRequest;

class UploadAutoSellPeriodSupportingDocumentRequest extends FormRequest
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
            'media' => ['required', 'file', 'mimes:pdf', 'max:10240'], // Max 10MB
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'media.required' => __('validation.supporting_document_required'),
            'media.file' => __('validation.supporting_document_must_be_file'),
            'media.mimes' => __('validation.supporting_document_must_be_pdf'),
            'media.max' => __('validation.supporting_document_max_size'),
        ];
    }
}
