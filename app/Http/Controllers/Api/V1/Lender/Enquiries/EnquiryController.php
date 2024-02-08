<?php

namespace App\Http\Controllers\Api\V1\Lender\Enquiries;

use App\Actions\Contracts\Enquiries\CreateEnquiry;
use App\Actions\Contracts\Enquiries\GetPaginatedUserEnquiries;
use App\Enums\Action;
use App\Enums\Area;
use App\Enums\Role;
use App\Enums\Subject;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Lender\Enquiries\StoreEnquiryRequest;
use App\Models\Enquiry;
use App\Transformers\EnquiryTransformer;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Grantify\Support\Roles\LenderAdmin;

class EnquiryController extends Controller
{
    public function __construct()
    {

        $this->middleware(
            'permission:'.
            perm(Area::Lender, [Subject::Enquiries, Action::Manage, Action::Index])
        )
            ->only('index');

        $this->middleware(
            'permission:'.
            perm(Area::Lender, [Subject::Enquiries, Action::Manage, Action::Create])
        )
            ->only('store');

        $this->middleware(
            'permission:'.
            perm(Area::Lender, [Subject::Enquiries, Action::Manage, Action::Show])
        )
            ->only('show');
    }

    /**
     * Display a listing of the resource.
     */
    public function index(
        Request $request,
        GetPaginatedUserEnquiries $getPaginatedUserEnquiries
    ): JsonResponse {
        $enquiries = $getPaginatedUserEnquiries->handle($request->user()->id);

        return fractal($enquiries, new EnquiryTransformer())
            ->parseIncludes([
                'id',
                'subject',
                'status',
                'creation_date',
            ])
            ->respond();
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreEnquiryRequest $storeEnquiryRequest, CreateEnquiry $createEnquiry): JsonResponse
    {
        $data = $storeEnquiryRequest->validated();
        $data['user_id'] = ($user = $storeEnquiryRequest->user())->id;
        $data['role_id'] = $user->roles()
            ->whereIn('name', Area::roles(Area::Lender))
            ->firstOrFail()
            ->id;

        $enquiry = $createEnquiry->handle($data);

        return fractal($enquiry, new EnquiryTransformer())
            ->parseIncludes([
                'id',
                'subject',
                'status',
                'creation_date',
            ])
            ->respond();
    }

    /**
     * Display the specified resource.
     *
     *
     * @throws AuthorizationException
     */
    public function show(Enquiry $enquiry): JsonResponse
    {
        $this->authorize('view', $enquiry);
        //there is exist gate for each user has LenderAdmin Role so this user cant go to policy and can see enquiry of another companies
        //but this condidtion will depand on tenancy if user not belong to company cant see enquiry
        if (is_null($enquiry->user)) {
            throw new ModelNotFoundException();
        }

        return fractal($enquiry, new EnquiryTransformer())
            ->parseIncludes([
                'id',
                'subject',
                'status',
                'creation_date',
                'creator',
                'body',
            ])
            ->respond();
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
