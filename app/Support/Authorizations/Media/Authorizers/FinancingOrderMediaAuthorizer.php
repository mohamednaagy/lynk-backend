<?php

namespace App\Support\Authorizations\Media\Authorizers;

use App\Enums\Action;
use App\Enums\Area;
use App\Enums\Role;
use App\Enums\Subject;
use App\Models\FinancingOrder;
use App\Models\User;
use App\Support\Authorizations\Media\Contracts\MediaAuthorizerContract;

class FinancingOrderMediaAuthorizer implements MediaAuthorizerContract
{
    public const AllowedRoles = [Role::Admin, Role::LenderSupervisor, Role::LenderAdmin];

    protected $user;

    protected $financingOrder;

    public function __construct(User $user, FinancingOrder $financingOrder)
    {
        $this->user = $user;
        $this->financingOrder = $financingOrder;
    }

    /**
     * @return bool
     */
    public function canAccess(): bool
    {
        return $this->user->company_id == $this->financingOrder->company_id
               && (
                   $this->user->hasRole(self::AllowedRoles)
                   || $this->user->hasAnyPermission(perm_as_array([Area::SuperAdmin, Area::Lender], [Subject::FinancingOrders, Action::Show]))
                   || $this->user->id == $this->financingOrder->creator_id
               );
    }
}
