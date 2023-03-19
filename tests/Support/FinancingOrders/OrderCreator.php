<?php

namespace Tests\Support\FinancingOrders;

use App\Enums\FinancingOrderStatus;
use App\Models\Company;
use App\Models\FinancingOrder;
use App\Models\User;
use Carbon\Carbon;
use Cknow\Money\Money;
use Propaganistas\LaravelPhone\PhoneNumber;
use Tests\Traits\InteractsWithCompany;

class OrderCreator
{
    use InteractsWithCompany;

    protected int $status;

    protected Company $lender;

    protected Money $amount;

    protected Money $sellingPrice;

    protected PhoneNumber $phone;

    protected string $nationalId;

    protected string $customerName;

    protected string|null $statusReason;

    protected User $creator;

    protected User $approver;

    protected ?bool $approvedAt;

    protected bool $requireVerification;

    public function __construct()
    {
        $this->status(FinancingOrderStatus::PendingApproval);
        $this->amount(money_parse_by_decimal(2000));
        $this->sellingPrice(money_parse_by_decimal(2500));
        $this->phoneNumber(phone('0500112233', 'SA'));
        $this->nationalId('2553451234');
        $this->customerName('Foo Bar');
        $this->requireVerification(false);
        $this->approvedAt($this->approver, $this->approvedAt ?? now());
        $this->statusReason(null);

        [$company] = $this->createLenderCompany();
        $this->lender($company);
    }

    public function status(int $status)
    {
        $this->status = $status;

        return $this;
    }

    public function lender(Company $lender)
    {
        $this->lender = $lender;

        return $this;
    }

    public function amount(Money $amount)
    {
        $this->amount = $amount;

        return $this;
    }

    public function sellingPrice(Money $sellingPrice)
    {
        $this->sellingPrice = $sellingPrice;

        return $this;
    }

    public function phoneNumber(PhoneNumber $phone)
    {
        $this->phone = $phone;

        return $this;
    }

    public function nationalId(string $nationalId)
    {
        $this->nationalId = $nationalId;

        return $this;
    }

    public function customerName(string $name)
    {
        $this->customerName = $name;

        return $this;
    }

    public function creator(User $creator)
    {
        $this->creator = $creator;

        return $this;
    }

    public function approvedAt(User $approver, null|Carbon $approvedAt)
    {
        $this->approvedAt = $approvedAt;
        $this->approver = $approver;

        return $this;
    }

    public function requireVerification(bool $shouldUserBeVerified)
    {
        $this->requireVerification = $shouldUserBeVerified;

        return $this;
    }

    public function statusReason(string|null $reason)
    {
        $this->statusReason = $reason;

        return $this;
    }

    public function commit()
    {
        return FinancingOrder::create([
            'company_id' => $this->lender->id,
            'approved_at' => $this->approvedAt,
            'creator_id' => $this->creator->id,
            'creator_type' => $this->creator->getMorphClass(),
            'customer_name' => $this->customerName,
            'national_id' => $this->nationalId,
            'phone_number' => $this->phone->formatE164(),
            'amount' => $this->amount,
            'selling_price' => $this->sellingPrice,
            'status' => $this->status,
            'is_verification_required' => $this->requireVerification,
        ]);
    }

    public static function __callStatic($name, $arguments)
    {
        return (new static())->{$name}($arguments);
    }
}
