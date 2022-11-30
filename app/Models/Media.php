<?php

namespace App\Models;

use Spatie\MediaLibrary\MediaCollections\Models\Media as baseMedia;

class Media extends baseMedia
{
    protected $connection = 'mysql';
}
