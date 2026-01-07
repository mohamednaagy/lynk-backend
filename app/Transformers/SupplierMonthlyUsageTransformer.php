<?php

namespace App\Transformers;

use App\Models\Media;
use League\Fractal\Resource\Primitive;
use League\Fractal\TransformerAbstract;

class SupplierMonthlyUsageTransformer extends TransformerAbstract
{
    protected array $availableIncludes = [
        'id',
        'uuid',
        'file_name',
        'name',
        'collection_name',
        'size',
        'mime_type',
        'created_at',
        'download_url',
    ];

    public function transform(Media $media): array
    {
        return [];
    }

    public function includeId(Media $media): Primitive
    {
        return $this->primitive($media->id);
    }

    public function includeUuid(Media $media): Primitive
    {
        return $this->primitive($media->uuid);
    }

    public function includeFileName(Media $media): Primitive
    {
        return $this->primitive($media->file_name);
    }

    public function includeName(Media $media): Primitive
    {
        return $this->primitive($media->name);
    }

    public function includeCollectionName(Media $media): Primitive
    {
        return $this->primitive($media->collection_name);
    }

    public function includeSize(Media $media): Primitive
    {
        return $this->primitive($media->size);
    }

    public function includeMimeType(Media $media): Primitive
    {
        return $this->primitive($media->mime_type);
    }

    public function includeCreatedAt(Media $media): Primitive
    {
        return $this->primitive(saudi_now('Y-m-d h:i A', $media->created_at));
    }

    public function includeDownloadUrl(Media $media): Primitive
    {
        return $this->primitive(formatMediaUrl($media->fileUrl));
    }
}
