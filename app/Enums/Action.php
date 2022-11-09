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
final class Action extends Enum
{
    const Manage = 'manage';

    const Index = 'index';

    const Create = 'create';

    const Show = 'show';

    const Edit = 'edit';

    const Delete = 'delete';

    const Approve = 'approve';

    const Reject = 'reject';

    const Cancel = 'cancel';

    const Proceed = 'proceed';

    const GetStats = 'getStats';

    const ResendInvitation = 'resendInvitation';

    const GetBalance = 'getBalance';

    const CalculateOrderCost = 'calculateOrderCost';

    const GetTransactions = 'getTransactions';

    const Charge = 'charge';

    const SyncStatusWithEdaat = 'syncStatusWithEdaat';
}
