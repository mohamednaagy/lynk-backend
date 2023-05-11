<?php

namespace App\Http\Controllers\Api\V1\Lender\Media;

use App\Enums\ErrorCode;
use App\Http\Controllers\Controller;
use App\Models\Media;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class DownloadMediaFile extends Controller
{
    /**
     * Summary of __invoke
     *
     * @param  Media  $media
     * @return mixed
     */
    public function __invoke($media)
    {
        $media = Media::where('uuid', $media)->firstOrFail();

        try {
            return Storage::disk($media->disk)->download($media->getPath());
        } catch (\Throwable $th) {
            return $this->errorResponse($th->getMessage(), Response::HTTP_NOT_FOUND, ErrorCode::FILE_NOT_FOUND);
        }
    }
}
