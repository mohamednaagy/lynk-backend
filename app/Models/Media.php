<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Config;
use Spatie\MediaLibrary\MediaCollections\Models\Media as baseMedia;

class Media extends baseMedia
{
    use SoftDeletes;

    public function getConnectionName()
    {
        return Config::get('database.default', parent::getConnectionName());
    }

    protected function fileUrl(): Attribute
    {
        return Attribute::make(
            fn () => route('api.v1.media.download', ['media' => $this->uuid])
        );
    }
}
