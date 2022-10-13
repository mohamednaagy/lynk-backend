<?php
namespace App\Models;

use Spatie\MediaLibrary\HasMedia;
use App\Enums\FinancingOrderStatus;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\InteractsWithMedia;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class FinancingOrder extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia, BelongsToTenant;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
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
