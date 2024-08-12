<?php

namespace App\Actions;

use App\Actions\Contracts\ResendInvitationToUser;
use App\Exceptions\UserDoesntBelongToCompany;
use App\Mail\Supplier\CompleteSupplierRegisterInvitation;
use App\Models\Company;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

class ResendInvitationToUserAction implements ResendInvitationToUser
{
    public function handle(Supplier|Company $company, User $user, string $redirect_url): void
    {
        $userBelongToCompany = $user->company->is($company);
        if (! $userBelongToCompany) {
            throw new UserDoesntBelongToCompany;
        }
        Mail::to($user)->send(new CompleteSupplierRegisterInvitation($user, $redirect_url));
    }
}
