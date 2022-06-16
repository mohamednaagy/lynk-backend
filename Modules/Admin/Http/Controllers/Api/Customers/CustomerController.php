<?php

namespace Modules\Admin\Http\Controllers\Api\Customers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Modules\Admin\Http\Requests\Customers\StoreCustomerRequest;
use Modules\Admin\Http\Requests\Customers\UpdateCustomerRequest;
use Modules\Admin\Http\Resources\CustomerResource;
use Modules\Customers\Actions\Customers\CreateCustomer;
use Modules\Customers\Actions\Customers\UpdateCustomer;
use Modules\Customers\Services\CustomerService;
use function response;

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
     * @param StoreCustomerRequest $storeCustomerRequest
     * @param CreateCustomer $createCustomer
     * @return JsonResponse
     */
    public function store(StoreCustomerRequest $storeCustomerRequest, CreateCustomer $createCustomer): JsonResponse
    {
        return DB::transaction(function () use($storeCustomerRequest, $createCustomer) {
            $validated = $storeCustomerRequest->validated();
            $user = $createCustomer->handle($validated);

            return response()->jsonFormat([], 201);
        });
    }

    /**
     * Show the specified resource.
     * @param int $id
     * @return CustomerResource|JsonResponse
     */
    public function show($id): CustomerResource|JsonResponse
    {
        $customer = $this->customerService->findCustomerById($id);

        return new CustomerResource($customer);
    }

    /**
     * Update the specified resource in storage.
     * @param UpdateCustomerRequest $updateCustomerRequest
     * @param int $id
     * @param UpdateCustomer $updateCustomer
     * @return JsonResponse
     */
    public function update(UpdateCustomerRequest $updateCustomerRequest, $id, UpdateCustomer $updateCustomer): JsonResponse
    {
        return DB::transaction(function () use($updateCustomerRequest, $id, $updateCustomer) {
            $customer = $this->customerService->findCustomerById($id);
            $validated = $updateCustomerRequest->validated();
            $updateCustomer->handle($validated, $customer);

            return response()->jsonFormat([]);
        });
    }

    /**
     * Remove the specified resource from storage.
     * @param int $id
     * @return JsonResponse
     */
    public function destroy($id): JsonResponse
    {
        $customer = $this->customerService->findCustomerById($id);
        $customer->delete();

        return response()->jsonFormat([]);
    }
}
