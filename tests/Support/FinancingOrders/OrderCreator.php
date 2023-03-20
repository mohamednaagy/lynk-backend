<?php

namespace Tests\Support\FinancingOrders;

use App\Enums\FinancingOrderStatus;
use App\Enums\Role;
use App\Models\FinancingOrder;
use Cknow\Money\Money;
use Tests\Traits\InteractsWithCompany;
use Tests\Traits\InteractsWithUser;

class OrderCreator extends OrderAttributesSetter
{
    use InteractsWithCompany, InteractsWithUser;

    protected array $attributes = [];

    public function __construct()
    {
        $this->status(FinancingOrderStatus::PendingApproval);
        $this->amount(money_parse_by_decimal(2000, Money::getDefaultCurrency()));
        $this->sellingPrice(money_parse_by_decimal(2500, Money::getDefaultCurrency()));
        $this->phoneNumber(phone('0500112233', 'SA'));
        $this->nationalId('2553451234');
        $this->customerName('Foo Bar');
        $this->requireVerification(false);
        $this->statusReason(null);

        [$company] = $this->createLenderCompany();
        $this->lender($company);

        $lenderSupervisor = $this->createLenderUser(
            $company->id,
            Role::LenderSupervisor
        );
        $this->approvedAt($lenderSupervisor, now());
    }

    public function commit(): CommittedOrder
    {
        return CommittedOrder::of(FinancingOrder::create($this->attributes));
    }
}
