<?php

namespace App\Http\Controllers\Api\V1\Admin\Media;

use App\Enums\Area;
use App\Enums\ErrorCode;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Admin\Media\DownloadMediaRequest;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DownloadMedia extends Controller
{
    /**
     * @param  DownloadMediaRequest  $downloadMediaRequest
     * @return JsonResponse|StreamedResponse
     *
     * @throws AuthorizationException
     */
    public function __invoke(DownloadMediaRequest $downloadMediaRequest)
    {
        $media = Media::findByUuid($downloadMediaRequest->validated('uuid'));

        $this->authorize('view', [$media, Area::SuperAdmin]);

        try {
            return Storage::disk($media->disk)->download($media->getPath());
        } catch (\Throwable $th) {
            return $this->errorResponse($th->getMessage(), Response::HTTP_NOT_FOUND, ErrorCode::FILE_NOT_FOUND);
        }
    }
}
