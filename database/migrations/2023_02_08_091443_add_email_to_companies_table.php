<?php

use App\Enums\CompanyType;
use App\Models\Company;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->string('notifications_email')->after('name')->nullable();
        });

        Company::whereNull('notifications_email')
            ->withWhereHas('users')
            ->orderBy('id')
            ->chunk(50, function ($companies) {
                $companies->each(
                    function ($company) {
                        $role = null;

                        if ($company->type->is(CompanyType::Lender)) {
                            $role = \App\Enums\Role::LenderAdmin;
                        } elseif ($company->type->is(CompanyType::Trader)) {
                            $role = \App\Enums\Role::TraderAdmin;
                        } else {
                            return;
                        }

                        $user = $company->users()
                            ->whereNotNull('email')
                            ->role($role)
                            ->first();

                        if ($user) {
                            $company->update(['notifications_email' => $user->email]);
                        }
                    }
                );
            });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn('notifications_email');
        });
    }
};
