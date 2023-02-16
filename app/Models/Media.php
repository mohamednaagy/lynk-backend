<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Facades\Config;
use Spatie\MediaLibrary\MediaCollections\Models\Media as baseMedia;

class Media extends baseMedia
{
    public function getConnectionName()
    {
        return Config::get('database.default', parent::getConnectionName());
    }

    protected function fileDownloadableUrl(): Attribute
    {
        return Attribute::make(
            fn () => route('api.v1.media.download', ['media' => $this->uuid])
        );
    }

    public function fileUrl(): string
    {
        return route('api.v1.media.download', ['media' => $this->uuid]);
    }
}
