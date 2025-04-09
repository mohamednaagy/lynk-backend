<?php

namespace App\Models;

use App\Enums\CompanyLenderClientType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class CompanyLenderClient extends Model
{
    use HasFactory, LogsActivity, SoftDeletes;

    protected $fillable = [
        'name',
        'company_id',
        'type',
        'national_id',
        'auto_complete_sell',
    ];

    protected $casts = [
        'type' => CompanyLenderClientType::class,
        'auto_complete_sell' => 'boolean',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['status']);
    }
}
