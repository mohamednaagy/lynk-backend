<?php

namespace Database\Seeders;

use App\Enums\CompanyType;
use App\Models\Company;
use Illuminate\Database\Seeder;

class CompanyNotificationsEmailSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Company::whereNull('notifications_email')
            ->with(['users'])
            ->whereHas('users')
            ->orderBy('id')->chunk(50, function ($companies) {
                $companies->map(
                    function ($company) {
                        if ($company->type->is(CompanyType::Trader)) {
                            $user = $company->users()
                                ->whereNotNull('email')
                                ->role(\App\Enums\Role::TraderAdmin)
                                ->first();
                            if ($user) {
                                $company->update(['notifications_email' => $user->email]);
                            }
                        }

                        if ($company->type->is(CompanyType::Lender)) {
                            $user = $company->users()
                                ->whereNotNull('email')
                                ->role(\App\Enums\Role::LenderAdmin)
                                ->first();
                            if ($user) {
                                $company->update(['notifications_email' => $user->email]);
                            }
                        }
                    }
                );
            });
    }
}
