<?php

namespace App\Models;

use Carbon\Carbon;
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

    public function getCreatedAtAttribute(): string
    {
        return saudi_now('Y-m-d h:i:s A', Carbon::parse($this->attributes['created_at']));
    }
}
