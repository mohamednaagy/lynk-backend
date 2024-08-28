<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SupplierLocation extends Model
{
    use HasFactory;

    protected $fillable = [
        'unique_identifier',
        'name',
        'description',
        'created_at',
        'company_id',
    ];

    public function supplier()
    {
        return $this->belongsTo(Company::class, 'company_id')->withTrashed();
    }
}
