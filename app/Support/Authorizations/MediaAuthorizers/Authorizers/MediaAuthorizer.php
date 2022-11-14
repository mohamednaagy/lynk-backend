<?php

namespace App\Support\Authorizations\MediaAuthorizers\Authorizers;

use App\Models\User;
use App\Support\Authorizations\MediaAuthorizers\MediaAuthorizerManager;
use App\Support\Authorizations\MediaAuthorizers\Utility\GetCollectionsByArea;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class MediaAuthorizer
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

    public function doesUserHaveAccessToModel()
    {
        $manager = (new MediaAuthorizerManager($this->user, $this->media->model))
            ->getMediaAuthorizerManager();

        return $manager->canAccess();
    }

    /**
     * Summary of checker
     *
     * @return bool
     */
    public function canAccess(): bool
    {
        return $this->doesAreaHaveAccessToCollection($this->area)
        && $this->doesUserHaveAccessToModel();
    }
}
