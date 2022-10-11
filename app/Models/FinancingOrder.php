<?php
namespace App\Models;

use App\Enums\FinancingOrderStatus;
use Spatie\MediaLibrary\HasMedia;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\InteractsWithMedia;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class FinancingOrder extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'company_id',
        'reference_number',
        'national_id',
        'amount',
        'selling_price',
        'status'
    ];

    public function registerMediaCollections(): void
    {
        $this
        ->addMediaCollection('contract')
        ->singleFile();

        $this
        ->addMediaCollection('power_of_attorney')
        ->singleFile();
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function getPowerOfAttorneyAttribute()
    {
        return $this->getFirstMediaUrl('power_of_attorney');
    }

    public function getContractAttribute()
    {
        return $this->getFirstMediaUrl('contract');
    }

    public function getStatusAttribute($value)
    {
        $status = FinancingOrderStatus::fromValue((int)$value);

        return $status->description;
    }
}
