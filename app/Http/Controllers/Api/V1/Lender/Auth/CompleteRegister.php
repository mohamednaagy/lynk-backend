<?php
namespace App\Http\Controllers\Api\V1\Lender\Auth;

use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Transformers\UserTransformer;
use App\Actions\Contracts\Lenders\Auth\UserCompleteRegister;
use App\Http\Requests\V1\Lender\Auth\CompleteRegisterRequest;

class CompleteRegister extends Controller
{
    public function __construct()
    {
        $this->middleware(['signed', 'throttle:6,1']);
    }

    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function __invoke(User $user, CompleteRegisterRequest $request, UserCompleteRegister $userCompleteRegister)
    {
        $user = $userCompleteRegister->handle($user, $request->validated());
        return fractal($user, new UserTransformer)->respond();
    }
}
