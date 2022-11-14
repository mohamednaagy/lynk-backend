<?php

namespace App\Support\Authorizations\MediaAuthorizers\Authorizers;

use App\Enums\Action;
use App\Enums\Area;
use App\Enums\Role;
use App\Enums\Subject;
use App\Models\User;
use App\Support\Authorizations\MediaAuthorizers\Contracts\MediaAuthorizerContract;
use App\Support\Authorizations\Utility\GetCollectionsByArea;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class FinancingOrderMediaAuthorizer implements MediaAuthorizerContract
{
    public const AllowedRoles = [Role::Admin, Role::LenderSupervisor, Role::LenderAdmin];

    protected $media;

    protected $user;

    protected $getMediaCollectionByArea;

    protected $area;

    public function __construct(User $user, Media $media, $area)
    {
        $this->media = $media;
        $this->user = $user;
        $this->area = $area;
    }

    /**
     * Summary of isAreaHasAccessToCollection
     *
     * @param  mixed  $area
     * @return bool
     */
    public function doesAreaHaveAccessToCollection($area)
    {
        $getCollectionsByArea = new GetCollectionsByArea();

        return in_array($this->media->collection_name, $getCollectionsByArea($area));
    }

    /**
     * Summary of checker
     *
     * @return bool
     */
    public function canAccess(): bool
    {
        $order = $this->media->model;

        return $this->doesAreaHaveAccessToCollection($this->area)
        &&
         $this->user->company_id == optional($order)->company_id
        &&
        (
            $this->user->hasRole(self::AllowedRoles)
            || $this->user->hasAnyPermission(perm_to([Area::SuperAdmin, Area::Lender], [Subject::FinancingOrders, Action::Show]))
            || $this->user->id == optional($order)->creator_id
        );
    }
}
