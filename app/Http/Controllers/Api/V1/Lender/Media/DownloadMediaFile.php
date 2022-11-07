<?php

namespace App\Http\Controllers\Api\V1\Lender\Media;

use App\Enums\Area;
use App\Enums\ErrorCode;
use App\Http\Controllers\Controller;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Symfony\Component\HttpFoundation\Response;

class DownloadMediaFile extends Controller
{
    /**
     * Summary of __invoke
     *
     * @param  Media  $media
     * @return mixed
     */
    public function __invoke(Media $media)
    {
        $this->authorize('view', [$media, Area::Lender]);

        try {
            return response()->download($media->getPath());
        } catch (\Throwable $th) {
            return $this->errorResponse($th->getMessage(), Response::HTTP_NOT_FOUND, ErrorCode::FILE_NOT_FOUND);
        }
    }
}
