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

    const Charge = 'charge';

    const Proceed = 'proceed';

    const SyncStatusWithEdaat = 'syncStatusWithEdaat';

    const Calculate = 'calculate';
}
