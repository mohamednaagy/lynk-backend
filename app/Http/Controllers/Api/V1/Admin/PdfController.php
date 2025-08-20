<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Api\V1\PdfController as BasePdfController;
use App\Http\Requests\GeneratePdfRequest;
use Illuminate\Http\JsonResponse;

class PdfController extends BasePdfController
{
    public function generate(GeneratePdfRequest $request): JsonResponse
    {
        return parent::generate($request);
    }
}
