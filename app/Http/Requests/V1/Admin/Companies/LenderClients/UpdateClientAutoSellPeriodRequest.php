<?php

namespace App\Http\Requests\V1\Admin\Companies\LenderClients;

use App\Enums\MediaCollections\ClientAutoSellPeriodMediaCollection;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateClientAutoSellPeriodRequest extends FormRequest
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
            'media' => ['nullable', 'array'],
            'media.*.file' => ['required_with:media.*', 'file', 'mimes:pdf', 'max:10240'], // Max 10MB
            'media.*.type' => [
                'required_with:media.*',
                'string',
                Rule::in(ClientAutoSellPeriodMediaCollection::getValues()),
            ],
            'delete_previous_supporting_sell_document_media' => ['nullable', 'boolean'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'media.*.file.required_with' => __('validation.media_file_required'),
            'media.*.file.file' => __('validation.media_file_must_be_file'),
            'media.*.file.mimes' => __('validation.media_file_must_be_pdf'),
            'media.*.file.max' => __('validation.media_file_max_size'),
            'media.*.type.required_with' => __('validation.media_type_required'),
            'media.*.type.in' => __('validation.media_type_invalid'),
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            // Ensure that if we're not deleting previous media, we have at least some media to upload
            if (! $this->boolean('delete_previous_supporting_sell_document_media') && ! $this->has('media')) {
                $validator->errors()->add('media', __('validation.media_or_delete_required'));
            }
        });
    }
}
