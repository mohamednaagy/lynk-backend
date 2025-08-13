<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CommodityTypeStatisticsView extends Model
{
    protected $table = 'commodity_type_statistics_view';

    public $timestamps = false;

    protected $primaryKey = 'commodity_type_id';
}
