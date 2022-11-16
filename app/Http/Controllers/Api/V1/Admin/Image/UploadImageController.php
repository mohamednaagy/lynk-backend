<?php

namespace App\Http\Controllers\Api\V1\Admin\Image;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Admin\Image\ImageRequest;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class UploadImageController extends Controller
{
    public function store(ImageRequest $request)
    {
        $imageName = Str::random().'.'.$request->image->getClientOriginalExtension();
        $path = Storage::disk('public_disk')->putFileAs('project/image', $request->image, $imageName);
        $url = env('APP_URL').Storage::url($path ?? '');

        return $url;
    }
}
