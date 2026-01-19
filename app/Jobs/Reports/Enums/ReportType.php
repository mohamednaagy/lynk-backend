<?php

declare(strict_types=1);

namespace App\Jobs\Reports\Enums;

use BenSampo\Enum\Enum;

final class ReportType extends Enum
{
    public const SupplierMonthlyUsage = 'supplier_monthly_usage';

    public const OrderList = 'order_list';
}
