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
            'document_type.required' => 'Document type is required. Please provide a valid document type.',
            'document_type.string' => 'Document type must be a string.',
            'document_type.in' => 'Invalid document type provided. Please check the available document types.',
            'context.required' => 'Context object is required.',
            'context.array' => 'Context must be an object.',
            'context.trader_order_id.integer' => 'Trader order ID must be a valid integer.',
            'context.trader_order_id.exists' => 'Trader order not found.',
            'context.transaction_id.integer' => 'Transaction ID must be a valid integer.',
            'context.transaction_id.exists' => 'Transaction not found.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'document_type' => 'document type',
            'context' => 'context',
            'context.trader_order_id' => 'trader order ID',
            'context.transaction_id' => 'transaction ID',
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
                "Document type '{$documentType->value}' requires a trader order ID"
            );
        }

        if ($documentType->requiresTransaction() && ! $this->context['transaction_id'] ?? null) {
            $validator->errors()->add(
                'context.transaction_id',
                "Document type '{$documentType->value}' requires a transaction ID"
            );
        }

        // Ensure at least one context field is provided
        if (! ($this->context['trader_order_id'] ?? null) && ! ($this->context['transaction_id'] ?? null)) {
            $validator->errors()->add(
                'context',
                'At least one context field (trader_order_id or transaction_id) must be provided'
            );
        }
    }
}
