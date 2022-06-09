<?php

namespace Modules\Admin\Services\Api;

use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;

class CustomerService
{
    /**
     * Get a list of paginated customer.
     * @return LengthAwarePaginator
     */
    public function getCustomers(): LengthAwarePaginator {
        return User::role('customer')->paginate(10);
    }

    /**
     * Find a customer by id.
     * @param int $id
     * @return User|null
     */
    public function findCustomerById(int $id): User|null {
        return User::role('customer')->find($id);
    }
}
