<?php

namespace App\Support\Authorizations\Media\Authorizers;

use App\Models\FinancingOrder;
use App\Models\User;
use App\Support\Authorizations\Media\Contracts\MediaAuthorizerContract;
use App\Support\Authorizations\Media\Utilities\GetCollectionsByArea;
use Exception;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class MediaAuthorizer implements MediaAuthorizerContract
{
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

    public function resolveAuthorizerByModel()
    {
        $authorizer = match (get_class($this->media->model)) {
            FinancingOrder::class => new FinancingOrderMediaAuthorizer($this->user, $this->media->model),
            default => throw new Exception(__('error.media_class_not_supported'))
        };

        return $authorizer->canAccess();
    }

    /**
     * Summary of checker
     *
     * @return bool
     */
    public function canAccess(): bool
    {
        return $this->doesAreaHaveAccessToCollection($this->area)
        && $this->resolveAuthorizerByModel();
    }
}
