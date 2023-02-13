<?php

namespace App\Transformers;

use App\Enums\Area;
use App\Models\User;
use Illuminate\Database\LazyLoadingViolationException;
use League\Fractal\Resource\Primitive;
use League\Fractal\TransformerAbstract;
use Modules\Grantify\Facades\Grantify;
use Spatie\Permission\Models\Permission;

class UserTransformer extends TransformerAbstract
{
    protected string|null $area = null;

    protected array $defaultIncludes = [];

    protected array $availableIncludes = [
        'id',
        'first_name',
        'last_name',
        'email',
        'role',
        'roles',
        'company',
        'is_email_verified',
        'permissions',
        'locale',
        'phone_number',
        'phone_country_code',
        'formatted_phone_number',
        'orders_count',
        'is_active',
        'is_invitation_accepted',
    ];

    public function __construct(string $area = null)
    {
        $this->area = $area;
    }

    public function transform(User $user)
    {
        return [];
    }

    public function includeId(User $user): Primitive
    {
        return $this->primitive($user->id);
    }

    public function includeFirstName(User $user): Primitive
    {
        return $this->primitive($user->first_name);
    }

    public function includeLastName(User $user): Primitive
    {
        return $this->primitive($user->last_name);
    }

    public function includeEmail(User $user): Primitive
    {
        return $this->primitive($user->email);
    }

    public function includeIsEmailVerified(User $user)
    {
        return $this->primitive($user->hasVerifiedEmail());
    }

    public function includeRole(User $user)
    {
        $query = $this->getRolesBasedOnArea($user);

        return $this->primitive($query->first()->name);
    }

    public function includeRoles(User $user)
    {
        $query = $this->getRolesBasedOnArea($user);

        return $this->primitive($query->get()->pluck('name'));
    }

    public function includeCompany(User $user)
    {
        return $this->item($user->company, new CompanyTransformer());
    }

    public function includePermissions(User $user)
    {
        $roles = $this->getRolesBasedOnArea($user);
        $directPermissions = $this->getPermissionsBasedOnArea($user);
        $permissions = Permission::role($roles)->get()->merge($directPermissions);

        $subjectPermissions = Grantify::transformPermissionsToSubjectAction($permissions);

        return $this->primitive($subjectPermissions);
    }

    public function includeFormattedPhoneNumber(User $user): Primitive
    {
        return $this->primitive($user->phone_number);
    }

    public function includePhoneNumber(User $user): Primitive
    {
        return $this->primitive($user->mobileDialingPhoneNumber);
    }

    public function includePhoneCountryCode(User $user): Primitive
    {
        return $this->primitive($user->phoneNumberCountryCode);
    }

    protected function getRolesBasedOnArea(User $user)
    {
        $roles = $user->roles;

        return match ($this->area) {
            Area::Lender, Area::SuperAdmin => $roles->whereIn('name', Area::roles($this->area)),
            default => $roles
        };
    }

    protected function getPermissionsBasedOnArea(User $user)
    {
        $permissions = $user->permissions;

        return match ($this->area) {
            Area::Lender,
            Area::SuperAdmin => $permissions->filter(fn ($item) => false !== stripos($item, $this->area)),
            default => $permissions
        };
    }

    public function includeLocale(User $user): Primitive
    {
        return $this->primitive($user->locale);
    }

    public function includeOrdersCount(User $user): Primitive
    {
        if (is_null($user->orders_count)) {
            throw new LazyLoadingViolationException($user, 'orders_count');
        }

        return $this->primitive((int) $user->orders_count);
    }

    public function includeIsActive(User $user): Primitive
    {
        return $this->primitive($user->is_active);
    }

    public function includeIsInvitationAccepted(User $user): Primitive
    {
        return $this->primitive((bool) $user->password);
    }
}
