<?php

namespace Tests\Traits;

use App\Enums\Area;
use App\Enums\CompanyType;
use App\Enums\FinancingOrderStatus;
use App\Enums\Role;
use App\Enums\WalletType;
use App\Models\Company;
use App\Models\EdaatInvoice;
use App\Models\FinancingOrder;
use App\Models\User;
use App\Support\Wallets\Contracts\TransactionServiceInterface;
use Exception;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Modules\Grantify\Facades\Grantify;

trait InteractsWithCompany
{
    /**
     * Summary of createTraderAdmin
     *
     * @param  string  $email
     * @param  array  $data
     * @return mixed
     */
    public function createTraderUser(
        $role = Role::TraderAdmin,
        string $email = 'traderAdmin@bim.com',
        array $data = []
    ): mixed {
        $admin = User::factory()->create(
            array_merge([
                'email' => $email,
                'password' => bcrypt('12345678'),
            ], $data)
        );

        Grantify::assignRoleToModel($admin, $role);

        return $admin;
    }

    /**
     * @param  array  $data
     * @return Company
     */
    public function createCompanyWithoutWallet(
        array $data = []
    ): Company {
        return Company::factory()->create(array_merge([
            'first_name' => 'firstName',
            'last_name' => 'lastName',
            'phone_country_code' => 'SA',
            'phone_number' => '503811000',
            'email' => 'test@uselynk.test',
            'password' => 'Qwer@1234',
            'source' => 'Postman',
        ], $data));
    }

    /**
     * @param  int  $walletInitialAmount
     * @param  array  $data
     * @return array
     *
     * @throws BindingResolutionException
     */
    public function createCompany(
        int $walletInitialAmount = 2000,
        array $data = []
    ): array {
        $company = $this->createCompanyWithoutWallet($data);

        $wallet = $company->createWallet(WalletType::CompanyWallet, 'SAR');

        app()->make(TransactionServiceInterface::class)->deposit(
            $wallet,
            \money($walletInitialAmount, 'SAR'),
            1,
            1,
            []
        );

        return [
            $company,
            $wallet,
        ];
    }

    public function createCompanyByArea($area, $walletInitialAmount = 2000, $data = [])
    {
        if (! in_array($area, array_values(Area::asArray()))) {
            throw new Exception(__('error.area_not_exists'));
        }

        $type = ($area == Area::Trader)
            ? CompanyType::Trader
            : CompanyType::Lender;

        return $this->createCompany($walletInitialAmount, array_merge(['type' => $type], $data));
    }

    /**
     * @param  int  $companyId
     * @param  string  $role
     * @param  string  $email
     * @param  array  $data
     * @return Collection|Model|mixed
     */
    public function createLenderUser(
        int $companyId,
        string $role,
        string $email = 'lender@bim.com',
        array $data = []
    ): mixed {
        $lenderUser = User::factory()->create(array_merge([
            'email' => $email,
            'password' => bcrypt('12345678'),
            'company_id' => $companyId,
        ], $data));

        Grantify::assignRoleToModel($lenderUser, $role);

        return $lenderUser;
    }

    /**
     * Summary of createUser
     *
     * @param  string  $email
     * @param  array  $data
     * @return mixed
     */
    public function createUser(
        $role = Role::Admin,
        array $data = []
    ): mixed {
        $user = User::factory()->create(
            array_merge([
                'password' => bcrypt('12345678'),
            ], $data)
        );

        Grantify::assignRoleToModel($user, $role);

        return $user;
    }

    /**
     * @param  int  $companyId
     * @param  int  $userId
     * @param  array  $data
     * @return FinancingOrder|Model|Builder
     */
    public function createOrder(int $companyId, int $userId, array $data = []): FinancingOrder|Model|Builder
    {
        return FinancingOrder::query()->create(array_merge([
            'company_id' => $companyId,
            'approved_at' => Carbon::now(),
            'creator_id' => $userId,
            'creator_type' => (new User)->getMorphClass(),
            'national_id' => '2553451234',
            'phone_number' => '+966500112233',
            'amount' => 200,
            'selling_price' => 220,
            'status' => FinancingOrderStatus::WaitingClientWakala,
            'is_verification_required' => true,
        ], $data));
    }

    public function createEdaatInvoice(int $companyId, int $userId, array $data = []): Model|Builder
    {
        return EdaatInvoice::query()->create(array_merge([
            'company_id' => $companyId,
            'creator_id' => $userId,
            'invoice_number' => Str::uuid(),
            'amount' => 1,
            'status' => 1,
        ], $data));
    }

    public function assertLenderUserCannotAccess($request)
    {
        $roles = Area::roles(Area::Lender);

        [$company] = $this->createCompanyByArea(Area::Lender);

        foreach ($roles as $role) {
            $user = $this->createLenderUser($company->id, $role, (string) Str::uuid().'@test.test');
            $request($user, $role)->assertStatus(403);
        }

        return $request;
    }

    public function assertStatusCodeForAllRolesExceptForArea($status, string $exceptedArea, $request)
    {
        $areas = Area::asArray();
        foreach ($areas as $area) {
            if ($area != $exceptedArea) {
                $this->assertStatusCodeForAreaRoles($status, $area, $request);
            }
        }
    }

    public function assertStatusCodeForAreaRoles($status, string $area, $request)
    {
        $areaRoles = Area::roles($area);
        foreach ($areaRoles as $role) {
            if (! is_array($role)) {
                if ($area == Area::SuperAdmin) {
                    $user = $this->createUser($role);
                    $request($user, $role)->assertStatus($status);

                    continue;
                }

                [$company] = $this->createCompanyByArea(
                    $area,
                    2000
                );

                $user = $this->createLenderUser($company->id, $role, (string) Str::uuid().'@test.test');
                $request($user, $role)->assertStatus($status);
            }
        }
    }

    public function assertStatusCodeToSpecificRoles(int $status, array $roles, $request)
    {
        [$company] = $this->createCompany(
            2000,
            [
                'company_cr' => (string) Str::uuid(),
            ]
        );

        foreach ($roles as $role) {
            $user = $this->createLenderUser($company->id, $role, (string) Str::uuid().'@test.test');
            $request($user, $role)->assertStatus($status);
        }
    }
}
