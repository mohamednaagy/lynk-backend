<?php

namespace App\Providers;

use App\Actions\AssignPermissionToUserAction;
use App\Actions\AssignRoleToUserAction;
use App\Actions\Companies\CreateCompanyAction;
use App\Actions\Companies\GetCompaniesAction;
use App\Actions\Companies\GetCompanyUsersAction;
use App\Actions\Contracts\AssignPermissionToUser;
use App\Actions\Contracts\AssignRoleToUser;
use App\Actions\Contracts\Companies\CreateCompany;
use App\Actions\Contracts\Companies\GetCompanies;
use App\Actions\Contracts\Companies\GetCompanyUsers;
use App\Actions\Contracts\CreateAdminWithRoleAndPermission;
use App\Actions\Contracts\CreateCustomerWithRoleAndPermission;
use App\Actions\Contracts\CreateUser;
use App\Actions\Contracts\FindUserByIdAndRole;
use App\Actions\Contracts\GetPaginatedUsersByRole;
use App\Actions\Contracts\GetSettingsArea;
use App\Actions\Contracts\GetSettingsClassInstance;
use App\Actions\Contracts\Lenders\Auth\CompleteUserRegistration;
use App\Actions\Contracts\Lenders\Auth\RegisterLender;
use App\Actions\Contracts\Lenders\CreateLenderUserWithRoleAndPermission;
use App\Actions\Contracts\Lenders\GetLenderBalance;
use App\Actions\Contracts\Lenders\GetPaginatedLenderUsers;
use App\Actions\Contracts\Lenders\UpdateLenderUserWithRoleAndPermission;
use App\Actions\Contracts\ListSettings;
use App\Actions\Contracts\LoginUser;
use App\Actions\Contracts\Orders\ApproveOrder;
use App\Actions\Contracts\Orders\CreateFinancingOrder;
use App\Actions\Contracts\Orders\GetPaginatedFinancingOrder;
use App\Actions\Contracts\Orders\RejectOrder;
use App\Actions\Contracts\Orders\UpdateFinancingOrder;
use App\Actions\Contracts\SyncPermissionToUser;
use App\Actions\Contracts\SyncRoleToUser;
use App\Actions\Contracts\UpdateAdminWithRoleAndPermission;
use App\Actions\Contracts\UpdateCustomerWithRoleAndPermission;
use App\Actions\Contracts\UpdateSettings;
use App\Actions\Contracts\UpdateUser;
use App\Actions\Contracts\Wallets\CalculateOrdersCost;
use App\Actions\Contracts\Wallets\CreateEdaatInvoice;
use App\Actions\Contracts\Wallets\GetTransactions;
use App\Actions\CreateAdminWithRoleAndPermissionAction;
use App\Actions\CreateCustomerWithRoleAndPermissionAction;
use App\Actions\CreateUserAction;
use App\Actions\FindUserByIdAndRoleAction;
use App\Actions\GetPaginatedUsersByRoleAction;
use App\Actions\GetSettingsAreaAction;
use App\Actions\GetSettingsClassInstanceAction;
use App\Actions\Lenders\Auth\CompleteUserRegistrationAction;
use App\Actions\Lenders\Auth\RegisterLenderAction;
use App\Actions\Lenders\CreateLenderUserWithRoleAndPermissionAction;
use App\Actions\Lenders\GetLenderBalanceAction;
use App\Actions\Lenders\GetPaginatedLenderUsersAction;
use App\Actions\Lenders\UpdateLenderUserWithRoleAndPermissionAction;
use App\Actions\ListSettingsAction;
use App\Actions\LoginUserAction;
use App\Actions\Orders\ApproveOrderAction;
use App\Actions\Orders\CreateFinancingOrderAction;
use App\Actions\Orders\GetPaginatedFinancingOrderAction;
use App\Actions\Orders\RejectOrderAction;
use App\Actions\Orders\UpdateFinancingOrderAction;
use App\Actions\SyncPermissionToUserAction;
use App\Actions\SyncRoleToUserAction;
use App\Actions\UpdateAdminWithRoleAndPermissionAction;
use App\Actions\UpdateCustomerWithRoleAndPermissionAction;
use App\Actions\UpdateSettingsAction;
use App\Actions\UpdateUserAction;
use App\Actions\Wallets\CalculateOrdersCostAction;
use App\Actions\Wallets\CreateEdaatInvoiceAction;
use App\Actions\Wallets\GetTransactionsAction;
use Illuminate\Support\ServiceProvider;

class ActionsServiceProvider extends ServiceProvider
{
    public array $bindings = [
        LoginUser::class => LoginUserAction::class,
        RegisterLender::class => RegisterLenderAction::class,
        CreateUser::class => CreateUserAction::class,
        CreateCompany::class => CreateCompanyAction::class,
        AssignRoleToUser::class => AssignRoleToUserAction::class,
        AssignPermissionToUser::class => AssignPermissionToUserAction::class,
        UpdateUser::class => UpdateUserAction::class,
        SyncRoleToUser::class => SyncRoleToUserAction::class,
        SyncPermissionToUser::class => SyncPermissionToUserAction::class,
        FindUserByIdAndRole::class => FindUserByIdAndRoleAction::class,
        GetPaginatedUsersByRole::class => GetPaginatedUsersByRoleAction::class,
        CreateAdminWithRoleAndPermission::class => CreateAdminWithRoleAndPermissionAction::class,
        UpdateAdminWithRoleAndPermission::class => UpdateAdminWithRoleAndPermissionAction::class,
        CreateCustomerWithRoleAndPermission::class => CreateCustomerWithRoleAndPermissionAction::class,
        UpdateCustomerWithRoleAndPermission::class => UpdateCustomerWithRoleAndPermissionAction::class,

        GetPaginatedLenderUsers::class => GetPaginatedLenderUsersAction::class,
        CreateLenderUserWithRoleAndPermission::class => CreateLenderUserWithRoleAndPermissionAction::class,
        UpdateLenderUserWithRoleAndPermission::class => UpdateLenderUserWithRoleAndPermissionAction::class,
        CompleteUserRegistration::class => CompleteUserRegistrationAction::class,

        ListSettings::class => ListSettingsAction::class,
        UpdateSettings::class => UpdateSettingsAction::class,
        GetSettingsArea::class => GetSettingsAreaAction::class,
        GetSettingsClassInstance::class => GetSettingsClassInstanceAction::class,
        // lenders
        CreateFinancingOrder::class => CreateFinancingOrderAction::class,
        UpdateFinancingOrder::class => UpdateFinancingOrderAction::class,
        GetPaginatedFinancingOrder::class => GetPaginatedFinancingOrderAction::class,
        ApproveOrder::class => ApproveOrderAction::class,
        RejectOrder::class => RejectOrderAction::class,
        CalculateOrdersCost::class => CalculateOrdersCostAction::class,
        GetTransactions::class => GetTransactionsAction::class,
        CreateEdaatInvoice::class => CreateEdaatInvoiceAction::class,
        GetLenderBalance::class => GetLenderBalanceAction::class,

        //auth
        GetCompanyUsers::class => GetCompanyUsersAction::class,
        GetCompanies::class => GetCompaniesAction::class,
    ];
}
