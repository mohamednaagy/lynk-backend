<?php

namespace App\Models;

use Illuminate\Support\Facades\Config;
use Spatie\MediaLibrary\MediaCollections\Models\Media as baseMedia;

class Media extends baseMedia
{
    public function getConnectionName()
    {
        return Config::get('database.default', parent::getConnectionName());
    }
}
