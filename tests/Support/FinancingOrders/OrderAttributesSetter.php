<?php

namespace Tests\Support\FinancingOrders;

use App\Models\Company;
use App\Models\User;
use Carbon\Carbon;
use Cknow\Money\Money;
use Propaganistas\LaravelPhone\PhoneNumber;
use Tests\Traits\InteractsWithCompany;

abstract class OrderAttributesSetter
{
    use InteractsWithCompany;

    protected array $attributes = [];

    public function status(int $status)
    {
        $this->attributes['status'] = $status;

        return $this;
    }

    public function lender(Company $lender)
    {
        $this->attributes['company_id'] = $lender->id;

        return $this;
    }

    public function amount(Money $amount)
    {
        $this->attributes['amount'] = $amount;

        return $this;
    }

    public function sellingPrice(Money $sellingPrice)
    {
        $this->attributes['selling_price'] = $sellingPrice;

        return $this;
    }

    public function phoneNumber(PhoneNumber $phone)
    {
        $this->attributes['phone_number'] = $phone;

        return $this;
    }

    public function nationalId(string $nationalId)
    {
        $this->attributes['national_id'] = $nationalId;

        return $this;
    }

    public function customerName(string $name)
    {
        $this->attributes['customer_name'] = $name;

        return $this;
    }

    public function creator(User $creator)
    {
        $this->attributes['creator_id'] = $creator->id;
        $this->attributes['creator_type'] = $creator->getMorphClass();

        return $this;
    }

    public function approvedAt(User $approver, null|Carbon $approvedAt)
    {
        $this->attributes['approved_at'] = $approvedAt;
        $this->attributes['approver_id'] = $approver->id;

        return $this;
    }

    public function requireVerification(bool $shouldUserBeVerified)
    {
        $this->attributes['is_verification_required'] = $shouldUserBeVerified;

        return $this;
    }

    public function statusReason(string|null $reason)
    {
        $this->attributes['status_reason'] = $reason;

        return $this;
    }
}
