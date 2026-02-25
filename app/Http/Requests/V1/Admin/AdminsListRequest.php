<?php

declare(strict_types=1);

namespace App\Http\Requests\V1\Admin;

use App\Enums\Role;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AdminsListRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['nullable', 'string', 'max:100'],
            'role' => ['nullable', Rule::in([Role::Admin, Role::Manager])],
        ];
    }
}
