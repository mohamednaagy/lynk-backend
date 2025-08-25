<?php

namespace App\Models;

use App\Support\Money\Casts\MoneyStringCast;
use Illuminate\Database\Eloquent\Model;

class CommodityTypeStatisticsView extends Model
{
    protected $table = 'commodity_type_statistics_view';

    public $timestamps = false;

    protected $primaryKey = 'commodity_type_id';

    protected $casts = [
        'total_value' => MoneyStringCast::class.':currency',
        'available_value' => MoneyStringCast::class.':currency',
        'reserved_value' => MoneyStringCast::class.':currency',
    ];

}
