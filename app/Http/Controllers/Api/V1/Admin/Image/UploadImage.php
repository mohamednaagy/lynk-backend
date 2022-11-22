<?php

namespace App\Http\Controllers\Api\V1\Admin\Image;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Admin\Image\ImageRequest;
use Illuminate\Support\Facades\Storage;

class UploadImage extends Controller
{
    public function store(ImageRequest $request)
    {
        $path = Storage::disk(config('filesystems.public_disk'))->put(config('filesystems.public_disk'), $request->image);
        $url = Storage::disk(config('filesystems.public_disk'))->url($path);

        return $this->successResponse(['url' => $url]);
    }
}
