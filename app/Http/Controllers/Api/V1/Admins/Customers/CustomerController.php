<?php

namespace App\Http\Controllers\Api\V1\Admins\Customers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Http\Resources\CustomerResource;
use App\Http\Requests\StoreCustomerRequest;
use App\Http\Requests\UpdateCustomerRequest;
use App\Actions\Contracts\FindUserByIdAndRole;
use App\Actions\Contracts\GetPaginatedUsersByRole;
use Illuminate\Http\Resources\Json\ResourceCollection;
use App\Actions\Contracts\CreateCustomerWithRoleAndPermission;
use App\Actions\Contracts\UpdateCustomerWithRoleAndPermission;

class CustomerController extends Controller
{
    /**
     * Display a listing of the resource.
     * @param GetPaginatedUsersByRole $getPaginatedUsersByRole
     * @return ResourceCollection
     */
    public function index(GetPaginatedUsersByRole $getPaginatedUsersByRole): ResourceCollection
    {
        $customers = $getPaginatedUsersByRole->handle(Role::Customer);

        return CustomerResource::collection($customers);
    }

    /**
     * Store a newly created resource in storage.
     * @param StoreCustomerRequest $storeCustomerRequest
     * @param CreateCustomerWithRoleAndPermission $createCustomerWithRoleAndPermission
     * @return JsonResponse
     */
    public function store(
        StoreCustomerRequest $storeCustomerRequest,
        CreateCustomerWithRoleAndPermission $createCustomerWithRoleAndPermission
    ): JsonResponse
    {
        return DB::transaction(function () use($storeCustomerRequest, $createCustomerWithRoleAndPermission) {
            $createCustomerWithRoleAndPermission->handle($storeCustomerRequest->validated());
            return $this->successResponse();
        });
    }

    /**
     * Show the specified resource.
     * @param int $id
     * @param FindUserByIdAndRole $findUserByIdAndRole
     * @return CustomerResource|JsonResponse
     */
    public function show(int $id, FindUserByIdAndRole $findUserByIdAndRole): CustomerResource|JsonResponse
    {
        $customer = $findUserByIdAndRole->handle($id, Role::Customer);
        if (!$customer) {
            return $this->errorResponse();
        }

        return new CustomerResource($customer);
    }

    /**
     * Update the specified resource in storage.
     * @param UpdateCustomerRequest $updateCustomerRequest
     * @param int $id
     * @param UpdateCustomerWithRoleAndPermission $updateCustomerWithRoleAndPermission
     * @param FindUserByIdAndRole $findUserByIdAndRole
     * @return JsonResponse
     */
    public function update(
        UpdateCustomerRequest $updateCustomerRequest,
        int $id,
        UpdateCustomerWithRoleAndPermission $updateCustomerWithRoleAndPermission,
        FindUserByIdAndRole $findUserByIdAndRole
    ): JsonResponse
    {
        return DB::transaction(function () use($updateCustomerRequest, $id, $updateCustomerWithRoleAndPermission, $findUserByIdAndRole) {
            $customer = $findUserByIdAndRole->handle($id, Role::Customer);
            if (!$customer) {
                return $this->errorResponse("not found");
            }

            $updateCustomerWithRoleAndPermission->handle($updateCustomerRequest->validated(), $customer);
            return $this->successResponse();
        });
    }

    /**
     * Remove the specified resource from storage.
     * @param int $id
     * @param FindUserByIdAndRole $findUserByIdAndRole
     * @return JsonResponse
     */
    public function destroy(int $id, FindUserByIdAndRole $findUserByIdAndRole): JsonResponse
    {
        $customer = $findUserByIdAndRole->handle($id, Role::Customer);
        if (!$customer) {
            return $this->errorResponse();
        }

        $customer->delete();

        return $this->successResponse();
    }
}
