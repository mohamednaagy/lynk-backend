<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'source',
        'amount',
        'national_id',
        'price',
        'customer_name',
        'status',
        'comment',
    ];

    public function orderInventories()
    {
        return $this->hasMany(OrderHasInventory::class, 'order_id');
    }
}
