<?php

namespace App\Models;

use Stancl\Tenancy\Database\Concerns\HasScopedValidationRules;
use Stancl\Tenancy\Database\Models\Tenant as BaseTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Company extends BaseTenant
{
    use HasFactory, HasScopedValidationRules;

    protected $table = 'companies';
    
    public $incrementing = true;
}
