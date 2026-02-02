<?php

namespace App\Support\QueryScoper\Scopes\Notifications;

use App\Support\QueryScoper\QueryScoper;
use Illuminate\Support\Facades\Validator;

class TradeRequestExpiredNotificationScope extends QueryScoper
{
    public function __construct(private readonly int $companyId) {}

    protected function prepareBuilder($builder, $data)
    {
        $companyId = $this->companyId;

        return $builder->where(function ($q) use ($companyId) {
            $q->admin()
                ->orWhere(function ($q) use ($companyId) {
                    $q->withLenderAdminForCompany($companyId);
                });
        });
    }

    protected function prepareData(): array
    {
        return [];
    }

    protected function validator($data)
    {
        return Validator::make($data, []);
    }
}
