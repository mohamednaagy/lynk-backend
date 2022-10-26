<?php

namespace App\Transformers;

use App\Enums\Area;
use App\Models\User;
use League\Fractal\Resource\Primitive;
use League\Fractal\TransformerAbstract;

class UserTransformer extends TransformerAbstract
{
    protected string|null $area = null;

    protected array $availableIncludes = [
        'roles',
        'formatted_phone_number',
        'phone_number',
        'country_code',
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
        ];
    }

    public function includeRoles(User $user)
    {
        $query = $this->getRolesQueryBasedOnArea($user);

        return $this->primitive($query->get()->pluck('name'));
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
