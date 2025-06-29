<?php

namespace App\Http\Controllers\Api\V1\Admin\Companies\LenderClients;

use App\Enums\Action;
use App\Enums\Area;
use App\Enums\MediaCollections\ClientAutoSellPeriodMediaCollection;
use App\Enums\Subject;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Admin\Companies\LenderClients\UpdateClientAutoSellPeriodRequest;
use App\Models\ClientAutoSellPeriod;
use App\Models\CompanyLenderClient;
use App\Models\Lender;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class ClientAutoSellPeriodController extends Controller
{
    public function __construct()
    {
        $this->middleware(
            'permission:'.
            perm(Area::SuperAdmin, [Subject::LenderClientAutoSellPeriodDocuments, Action::Edit, Action::Manage])
        );
    }

    /**
     * Update the auto sell period with media handling.
     */
    public function update(
        UpdateClientAutoSellPeriodRequest $request,
        Lender $lender,
        CompanyLenderClient $client,
        ClientAutoSellPeriod $clientAutoSellPeriod
    ): JsonResponse {
        // Handle media deletion if requested
        if ($request->boolean('delete_previous_supporting_sell_document_media')) {
            $clientAutoSellPeriod->clearMediaCollection(ClientAutoSellPeriodMediaCollection::SupportingDocument);
        }

        // Handle media upload if provided
        if ($request->has('media')) {
            foreach ($request->input('media') as $index => $mediaItem) {
                if (isset($mediaItem['type']) && $request->hasFile("media.{$index}.file")) {
                    $collectionName = $mediaItem['type'];

                    // Validate that the collection name exists in our enum
                    if (! in_array($collectionName, ClientAutoSellPeriodMediaCollection::getValues())) {
                        return response()->json([
                            'message' => __('error.invalid_media_collection_type'),
                            'errors' => ['media' => [__('error.invalid_media_collection_type')]],
                        ], Response::HTTP_UNPROCESSABLE_ENTITY);
                    }

                    // Add the media to the specified collection
                    $clientAutoSellPeriod->addMediaFromRequest("media.{$index}.file")->toMediaCollection($collectionName);
                }
            }
        }

        $allMedia = $clientAutoSellPeriod->getMedia(ClientAutoSellPeriodMediaCollection::SupportingDocument);

        return response()->json([
            'message' => __('common.updated_successfully'),
            'data' => [
                'id' => $clientAutoSellPeriod->id,
                'effective_start' => $clientAutoSellPeriod->effective_start->format('Y-m-d'),
                'effective_end' => $clientAutoSellPeriod->effective_end->format('Y-m-d'),
                'media' => $allMedia->map(function ($media) {
                    return [
                        'id' => $media->id,
                        'name' => $media->name,
                        'collection_name' => $media->collection_name,
                        'url' => $media->getUrl(),
                    ];
                }),
            ],
        ], Response::HTTP_OK);
    }
}
