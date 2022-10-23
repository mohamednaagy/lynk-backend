<?php

namespace App\Models;

use App\Enums\CompanyStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Stancl\Tenancy\Database\Concerns\HasScopedValidationRules;
use Stancl\Tenancy\Database\Models\Tenant as BaseTenant;

class Company extends BaseTenant
{
    use HasFactory, HasScopedValidationRules;

    protected $table = 'companies';

    public $incrementing = true;

    protected $casts = [
        'status' => CompanyStatus::class,
    ];

    public static function getCustomColumns(): array
    {
        return [
            'id',
            'name',
            'unique_name',
            'company_cr',
            'status',
            'order_cost',
        ];
    }
}
