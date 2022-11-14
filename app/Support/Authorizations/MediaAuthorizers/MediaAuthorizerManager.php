<?php

namespace App\Support\Authorizations\MediaAuthorizers;

use App\Models\FinancingOrder;
use App\Models\User;
use App\Support\Authorizations\MediaAuthorizers\Authorizers\FinancingOrderMediaAuthorizer;
use Exception;
use Illuminate\Database\Eloquent\Model;

class MediaAuthorizerManager
{
    protected $user;

    protected $model;

    public function __construct(User $user, Model $model)
    {
        $this->user = $user;
        $this->model = $model;
    }

    public function getMediaAuthorizerManager()
    {
        return match (get_class($this->model)) {
            FinancingOrder::class => new FinancingOrderMediaAuthorizer($this->user, $this->model),
            default => throw new Exception(__('error.media_class_not_supported'))
        };
    }
}
