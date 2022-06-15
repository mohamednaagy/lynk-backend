<?php

namespace Modules\Customers\Services;

use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Permission\Enums\Role;

class CustomerService
{
    /**
     * Get a list of paginated customer.
     * @return LengthAwarePaginator
     */
    public function getCustomers(): LengthAwarePaginator {
        return User::role(Role::Customer)->paginate();
    }

    /**
     * Find a customer by id.
     * @param int $id
     * @return User
     */
    public function findCustomerById(int $id): User {
        return User::role(Role::Customer)->findOrFail($id);
    }
}
