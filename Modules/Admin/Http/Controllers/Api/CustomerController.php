<?php

namespace Modules\Admin\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Routing\Controller;
use Modules\Admin\Actions\Api\CreateNewUser;
use Modules\Admin\Actions\Api\UpdateUser;
use Modules\Admin\Http\Requests\CreateUserRequest;
use Modules\Admin\Http\Requests\UpdateUserRequest;
use Modules\Admin\Http\Resources\CustomerResource;
use Modules\Admin\Services\Api\CustomerService;

class CustomerController extends Controller
{

    protected CustomerService $customerService;

    public function __construct(CustomerService $customerService)
    {
        $this->customerService = $customerService;
    }

    /**
     * Display a listing of the resource.
     * @return ResourceCollection
     */
    public function index(): ResourceCollection
    {
        return CustomerResource::collection($this->customerService->getCustomers());
    }

    /**
     * Store a newly created resource in storage.
     * @param CreateUserRequest $createUserRequest
     * @param CreateNewUser $createNewUser
     * @return JsonResponse
     */
    public function store(CreateUserRequest $createUserRequest, CreateNewUser $createNewUser): JsonResponse
    {
        $user = $createNewUser->handle($createUserRequest);
        return response()->jsonFormat([ 'message' => trans('admin::response.customer.created')], 201);
    }

    /**
     * Show the specified resource.
     * @param int $id
     * @return CustomerResource|JsonResponse
     */
    public function show($id): CustomerResource|JsonResponse
    {
        $customer = $this->customerService->findCustomerById($id);

        if (!$customer)
            return response()->jsonFormat(['message' => trans('admin::response.customer.not_found')], 404);

        return new CustomerResource($customer);
    }

/**
 * Update the specified resource in storage.
 * @param UpdateUserRequest $updateUserRequest
 * @param int $id
 * @param UpdateUser $updateUser
 * @return JsonResponse
 */
    public function update(UpdateUserRequest $updateUserRequest, $id, UpdateUser $updateUser): JsonResponse
    {
        $customer = $this->customerService->findCustomerById($id);

        if (!$customer)
            return response()->jsonFormat(['message' => trans('admin::response.customer.not_found')], 404);

        $updateUser->handle($updateUserRequest, $customer);

        return response()->jsonFormat([ 'message' => trans('admin::response.customer.updated')]);
    }

    /**
     * Remove the specified resource from storage.
     * @param int $id
     * @return JsonResponse
     */
    public function destroy($id): JsonResponse
    {
        $customer = $this->customerService->findCustomerById($id);

        if (!$customer)
            return response()->jsonFormat(['message' => trans('admin::response.customer.not_found')], 404);

        $customer->delete();
        return response()->jsonFormat(['message' => trans('admin::response.customer.deleted')]);
    }
}
