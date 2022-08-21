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
    const Index = 'Index';
    const Create = 'Create';
    const Show = 'Show';
    const Edit = 'Edit';
    const Delete = 'Delete';
    Const getRoles = 'getRoles';
    Const getPermissions = 'getPermissions';
}
