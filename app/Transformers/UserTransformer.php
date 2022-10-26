<?php

namespace App\Transformers;

use App\Models\User;
use League\Fractal\Resource\Primitive;
use League\Fractal\TransformerAbstract;

class UserTransformer extends TransformerAbstract
{
    protected array $availableIncludes = [
        'role',
        'formatted_phone_number',
        'phone_number',
        'country_code',
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

    public function includeRole(User $user): Primitive
    {
        return $this->primitive($user->getRoleNames()->first());
    }

    public function includeFormattedPhoneNumber(User $user): Primitive
    {
        return $this->primitive($user->phone_number);
    }

    public function includePhoneNumber(User $user): Primitive
    {
        return $this->primitive($user->phone_number->formatNational());
    }

    public function includeCountryCode(User $user): Primitive
    {
        return $this->primitive($user->phone_number->getCountry());
    }
}
