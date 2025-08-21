<?php

namespace App\Http\Requests;

use App\Enums\DocumentType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GeneratePdfRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization will be handled by middleware
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'document_type' => [
                'required',
                'string',
                Rule::in(DocumentType::getValues()),
            ],
            'context' => [
                'required',
                'array',
            ],
            'context.trader_order_id' => [
                'nullable',
                'integer',
                'exists:trader_orders,id',
            ],
            'context.transaction_id' => [
                'nullable',
                'integer',
                'exists:transactions,id',
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'document_type.required' => __('validation.pdf.document_type_required'),
            'document_type.string' => __('validation.pdf.document_type_string'),
            'document_type.in' => __('validation.pdf.document_type_invalid'),
            'context.required' => __('validation.pdf.context_required'),
            'context.array' => __('validation.pdf.context_array'),
            'context.trader_order_id.integer' => __('validation.pdf.trader_order_id_integer'),
            'context.trader_order_id.exists' => __('validation.pdf.trader_order_not_found'),
            'context.transaction_id.integer' => __('validation.pdf.transaction_id_integer'),
            'context.transaction_id.exists' => __('validation.pdf.transaction_not_found'),
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'document_type' => __('validation.attributes.document_type'),
            'context' => __('validation.attributes.context'),
            'context.trader_order_id' => __('validation.attributes.trader_order_id'),
            'context.transaction_id' => __('validation.attributes.transaction_id'),
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $this->validateContextRequirements($validator);
        });
    }

    /**
     * Validate that required context is provided based on document type
     */
    private function validateContextRequirements($validator)
    {
        // Only validate context requirements if document_type is present and valid
        if (! $this->document_type || ! in_array($this->document_type, DocumentType::getValues())) {
            return; // Let the main validation rules handle this error
        }

        $documentType = DocumentType::fromValue($this->document_type);

        if ($documentType->requiresTraderOrder() && ! $this->context['trader_order_id'] ?? null) {
            $validator->errors()->add(
                'context.trader_order_id',
                __('validation.pdf.trader_order_required', ['document_type' => $documentType->value])
            );
        }

        if ($documentType->requiresTransaction() && ! $this->context['transaction_id'] ?? null) {
            $validator->errors()->add(
                'context.transaction_id',
                __('validation.pdf.transaction_required', ['document_type' => $documentType->value])
            );
        }

        // Ensure at least one context field is provided
        if (! ($this->context['trader_order_id'] ?? null) && ! ($this->context['transaction_id'] ?? null)) {
            $validator->errors()->add(
                'context',
                __('validation.pdf.context_required_field')
            );
        }
    }
}
