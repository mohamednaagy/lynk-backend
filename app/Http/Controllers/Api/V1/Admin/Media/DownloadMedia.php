<?php

namespace App\Http\Controllers\Api\V1\Admin\Media;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Admin\Media\DownloadMediaRequest;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Stancl\Tenancy\Exceptions\TenantCouldNotBeIdentifiedById;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class DownloadMedia extends Controller
{
    /**
     * @param  DownloadMediaRequest  $downloadMediaRequest
     * @return BinaryFileResponse
     *
     * @throws TenantCouldNotBeIdentifiedById
     */
    public function __invoke(DownloadMediaRequest $downloadMediaRequest)
    {
        $media = Media::findByUuid($downloadMediaRequest->validated('uuid'));

        return response()->download($media->getPath(), $media->file_name);
    }
}
