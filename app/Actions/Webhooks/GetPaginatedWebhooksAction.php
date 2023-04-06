<?php

namespace App\Actions\Webhooks;

use App\Actions\Contracts\Webhooks\GetPaginatedWebhooks;
use App\Models\Webhook;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class GetPaginatedWebhooksAction implements GetPaginatedWebhooks
{
    /**
     * @param  int|null  $paginate
     * @return LengthAwarePaginator
     */
    public function handle(int $paginate = null): LengthAwarePaginator
    {
        return Webhook::query()
            ->with('company.webhooks')
            ->latest()
            ->paginate($paginate);
    }
}
