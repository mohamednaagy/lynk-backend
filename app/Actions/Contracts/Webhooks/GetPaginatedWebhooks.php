<?php

namespace App\Actions\Contracts\Webhooks;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface GetPaginatedWebhooks
{
    /**
     * @param  int|null  $paginate
     * @return LengthAwarePaginator
     */
    public function handle(int $paginate = null): LengthAwarePaginator;
}
