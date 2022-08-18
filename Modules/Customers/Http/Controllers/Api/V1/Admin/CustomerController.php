<?php

namespace Modules\Customers\Http\Controllers\Api\V1\Admin;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Modules\Permission\Enums\Role;
use App\Http\Controllers\Controller;
use App\Actions\Contracts\FindUserByIdAndRole;
use App\Actions\Contracts\GetPaginatedUsersByRole;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Modules\Customers\Http\Resources\CustomerResource;
use Modules\Customers\Http\Requests\StoreCustomerRequest;
use Modules\Customers\Http\Requests\UpdateCustomerRequest;
use Modules\Customers\Actions\Contracts\CreateCustomerWithRoleAndPermission;
use Modules\Customers\Actions\Contracts\UpdateCustomerWithRoleAndPermission;

class CustomerController extends Controller
{
    /**
     * Display a listing of the resource.
     * @param GetPaginatedUsersByRole $getPaginatedUsersByRole
     * @return ResourceCollection
     */
    public function index(GetPaginatedUsersByRole $getPaginatedUsersByRole): ResourceCollection
    {
        $customers = $getPaginatedUsersByRole(Role::Customer);

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
        try{
            return DB::transaction(function () use($storeCustomerRequest, $createCustomerWithRoleAndPermission) {
                $createCustomerWithRoleAndPermission($storeCustomerRequest->validated());
                return $this->successResponse();
        });
        }catch (\Exception $exception){
            return $this->errorResponse(message: $exception->getMessage());
        }
    }

    /**
     * Show the specified resource.
     * @param int $id
     * @param FindUserByIdAndRole $findUserByIdAndRole
     * @return CustomerResource|JsonResponse
     */
    public function show(int $id, FindUserByIdAndRole $findUserByIdAndRole): CustomerResource|JsonResponse
    {
        $customer = $findUserByIdAndRole($id, Role::Customer);
        if (!$customer)
            return $this->errorResponse();

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
        try{
        return DB::transaction(function () use($updateCustomerRequest, $id, $updateCustomerWithRoleAndPermission, $findUserByIdAndRole) {
            $customer = $findUserByIdAndRole($id, Role::Customer);
            if (!$customer)
                return $this->errorResponse("not found");

            $updateCustomerWithRoleAndPermission($updateCustomerRequest->validated(), $customer);
            return $this->successResponse();
        });
        }catch (\Exception $exception){
            return $this->errorResponse(message: $exception->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     * @param int $id
     * @param FindUserByIdAndRole $findUserByIdAndRole
     * @return JsonResponse
     */
    public function destroy(int $id, FindUserByIdAndRole $findUserByIdAndRole): JsonResponse
    {
        $customer = $findUserByIdAndRole($id, Role::Customer);
        if (!$customer)
            return $this->errorResponse();

        $customer->delete();

        return $this->successResponse();
    }
}
