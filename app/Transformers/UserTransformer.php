<?php

namespace App\Transformers;

use App\Enums\Area;
use App\Models\User;
use League\Fractal\TransformerAbstract;
use Modules\Grantify\Facades\Grantify;
use Spatie\Permission\Models\Permission;

class UserTransformer extends TransformerAbstract
{
    protected string|null $area = null;

    protected array $availableIncludes = [
        'roles',
        'is_email_verified',
        'permissions',
    ];

    public function __construct(string $area = null)
    {
        $this->area = $area;
    }

    public function transform(User $user)
    {
        return [
            'id' => $user->id,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'email' => $user->email,
            'phone_number' => $user->phone_number,
        ];
    }

    public function includeIsEmailVerified(User $user)
    {
        return $this->primitive($user->hasVerifiedEmail());
    }

    public function includeRoles(User $user)
    {
        $query = $this->getRolesQueryBasedOnArea($user);

        return $this->primitive($query->get()->pluck('name'));
    }

    public function includePermissions(User $user)
    {
        $rolesQuery = $this->getRolesQueryBasedOnArea($user);

        $subjectPermissions = Grantify::transformPermissionsToSubjectAction(
            Permission::role($rolesQuery->get())->get()
        );

        return $this->primitive($subjectPermissions);
    }

    protected function getRolesQueryBasedOnArea(User $user)
    {
        $query = $user->roles();

        $query = match ($this->area) {
            Area::Lender => $query->whereIn('name', Area::getRolesPerAreaMap()[$this->area]),
            Area::SuperAdmin => $query->whereIn('name', Area::getRolesPerAreaMap()[$this->area]),
        };

        return $query;
    }
}
