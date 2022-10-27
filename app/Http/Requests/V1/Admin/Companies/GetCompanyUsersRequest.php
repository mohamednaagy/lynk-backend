<?php

namespace App\Http\Requests\V1\Admin\Companies;

use Illuminate\Foundation\Http\FormRequest;

/**
 * @property string $area
 */
class GetCompanyUsersRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [];
    }

    public function all($keys = null): ?array
    {
        return array_merge(parent::all(), $this->route()->parameters());
    }
}
