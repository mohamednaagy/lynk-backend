<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CommodityItemType extends Model
{
    use HasFactory;

    protected $fillable = [
        'commodity_item_id',
        'commodity_type_id',
    ];
}
