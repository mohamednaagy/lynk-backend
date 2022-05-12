<?php

namespace Modules\Customers\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Customer extends Model
{
    use HasFactory;

    protected $fillable = [
        'first_name',
        'last_name',
        'phone_country_code',
        'phone_number',
        'email',
        'password',
    ];

    protected static function newFactory()
    {
        return \Modules\Customers\Database\factories\CustomerFactory::new();
    }
}
