<?php

namespace App\Http\Requests\V1\Admin\Companies\LenderClients;

use App\Models\CompanyLenderClient;
use Illuminate\Foundation\Http\FormRequest;

class DeleteLenderClientRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return CompanyLenderClient::where('id', $this->client->id)->where('company_id', $this->lender->id)->exists();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [];
    }
}
