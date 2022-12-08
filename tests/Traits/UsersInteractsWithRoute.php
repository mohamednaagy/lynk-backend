<?php

namespace Tests\Traits;

use App\Enums\Area;
use Illuminate\Support\Str;

trait UsersInteractsWithRoute
{
    public function assertLenderUserCannotAccess($request)
    {
        $roles = Area::roles(Area::Lender);

        [$company] = $this->createCompany(
            2000,
            [
                'company_cr' => Str::uuid(),
            ]
        );

        foreach ($roles as $role) {
            $user = $this->createLenderUser($company->id, $role, (string) Str::uuid().'@test.test');
            $request($user, $role)->assertStatus(403);
        }

        return $request;
    }
}
