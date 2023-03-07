<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

/**
 * @method static static Index()
 * @method static static Create()
 * @method static static Show()
 * @method static static Edit()
 * @method static static Delete()
 */
final class ClientMessage extends Enum
{
    const CommoditySoldToCustomer = 'client-sms.commodity_sold_to_customer';

    const MultiCommoditySoldToCustomer = 'client-sms.multi_commodity_sold_to_customer';

    const MultiCommoditySoldToCustomerUrl = 'client-sms.multi_commodity_sold_to_customer_url';

    const CommoditySoldToCustomerWithoutVerification = 'client-sms.commodity_sold_to_customer_without_verification';

    const MurabahaSaleCompleted = 'client-sms.murabaha_sale_completed';
}
