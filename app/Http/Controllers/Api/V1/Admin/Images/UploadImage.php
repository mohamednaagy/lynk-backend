<?php

namespace App\Http\Controllers\Api\V1\Admin\Images;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Admin\Images\UploadImageRequest;
use Illuminate\Support\Facades\Storage;

class UploadImage extends Controller
{
    public function store(UploadImageRequest $request)
    {
        $path = $request->image->store('images', config('filesystems.public_disk'));

        $url = Storage::disk(config('filesystems.public_disk'))->url($path);

        return $this->successResponse(['url' => $url]);
    }
}
