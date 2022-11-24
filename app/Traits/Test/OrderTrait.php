<?php

namespace App\Traits\Test;

use App\Enums\Role;
use App\Enums\WalletType;
use App\Models\Company;
use App\Models\FinancingOrder;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Modules\Grantify\Facades\Grantify;

trait OrderTrait
{
    /**
     * @return array
     */
    public function createCompanyDetails(): array
    {
        $company = Company::factory()->create([
            'first_name' => 'firstName',
            'last_name' => 'lastName',
            'phone_country_code' => 'SA',
            'phone_number' => '503811000',
            'email' => 'test@uselynk.test',
            'password' => 'Qwer@1234',
            'source' => 'Postman',
            'company_name' => 'companyName',
            'company_unique_name' => 'lynk05',
            'company_cr' => '12345678910',
        ]);

        $wallet = $company->createWallet([
            'name' => WalletType::CompanyWallet,
            'slug' => WalletType::CompanyWallet,
        ]);

        $wallet->deposit(2000);

        return [
            $company,
            $wallet,
        ];
    }

    /**
     * @param  int  $companyId
     * @param  Role  $role
     * @return Collection|Model|mixed
     */
    public function createLenderUser(int $companyId, string $role): mixed
    {
        $userLender = User::factory()->create([
            'email' => 'lender@bim.com',
            'password' => bcrypt('12345678'),
            'company_id' => $companyId,
        ]);

        Grantify::assignRoleToModel($userLender, $role);

        return $userLender;
    }

    /**
     * @param  int  $companyId
     * @param  int  $userId
     * @return Model|Builder
     */
    public function createOrder(int $companyId, int $userId): Model|Builder
    {
        return FinancingOrder::query()->create([
            'company_id' => $companyId,
            'approved_at' => Carbon::now(),
            'creator_id' => $userId,
            'creator_type' => User::class,
            'national_id' => '2553451234',
            'phone_number' => '+966500112233',
            'amount' => 200,
            'selling_price' => 220,
            'status' => 11,
            'is_verification_required' => 1,
        ]);
    }
}
