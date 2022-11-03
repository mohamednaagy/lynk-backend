<?php

namespace App\Actions\Contracts;

use Spatie\MediaLibrary\MediaCollections\FileAdder;

interface HasMedia extends \Spatie\MediaLibrary\HasMedia
{
    public function addMediaFromDisk(string $key, string $disk = null): FileAdder;
}
