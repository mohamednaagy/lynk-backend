<?php

namespace App\Support\Fractal\Serializers;

use League\Fractal\Serializer\ArraySerializer;

class DataArraySerializer extends ArraySerializer
{
    /**
     * {@inheritDoc}
     */
    public function collection(?string $resourceKey, array $data): array
    {
        return $resourceKey ? [$resourceKey => $data] : $data;
    }

    /**
     * {@inheritDoc}
     */
    public function item(?string $resourceKey, array $data): array
    {
        return $resourceKey ? [$resourceKey => $data] : $data;
    }

    /**
     * {@inheritDoc}
     */
    public function null(): ?array
    {
        return null;
    }
}
