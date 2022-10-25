<?php

namespace App\Transformers;

use App\Models\User;
use League\Fractal\TransformerAbstract;
use Modules\Grantify\Facades\Grantify;
use Spatie\Permission\Models\Permission;

class UserTransformer extends TransformerAbstract
{
    protected array $availableIncludes = [
        'roles',
        'is_email_verified',
        'permissions',
    ];

    public function transform(User $user)
    {
        return [
            'id' => $user->id,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'email' => $user->email,
        ];
    }

    public function includeIsEmailVerified(User $user)
    {
        return $this->primitive($user->hasVerifiedEmail());
    }

    public function includeRoles(User $user)
    {
        return $this->primitive($user->roles->pluck('name'));
    }

    public function includePermissions(User $user)
    {
        return $this->primitive(Grantify::transformPermissionsToSubjectAction(Permission::role($user->roles)->get()));
    }
}
