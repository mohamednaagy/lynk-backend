<?php

namespace App\Support\Authorizations;

use App\Actions\Contracts\getMediaCollectionByArea;
use App\Enums\Role;
use App\Models\User;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class FinancingOrderMediaAuthorize
{
    protected $media;

    protected $user;

    protected $getMediaCollectionByArea;

    public function __construct(User $user, Media $media)
    {
        $this->media = $media;
        $this->user = $user;
    }

    /**
     * Summary of isAreaHasAccessToCollection
     *
     * @param  mixed  $area
     * @return bool
     */
    public function doesAreaHasAccessToCollection($area)
    {
        return in_array($this->media->collection_name, app(getMediaCollectionByArea::class)->handle($area));
    }

    /**
     * Summary of checker
     *
     * @return bool
     */
    public function conditions()
    {
        $order = $this->media->model;

        return $this->user->company_id == optional($order)->company_id
        &&
        ($this->user->hasAnyRole([Role::Admin, Role::LenderSupervisor]) || $this->user->id == optional($order)->creator_id);
    }
}
