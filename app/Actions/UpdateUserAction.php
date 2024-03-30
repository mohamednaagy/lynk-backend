<?php

namespace App\Actions;

use App\Actions\Contracts\UpdateUser;
use App\Mail\ChangeEmail;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Mail;

class UpdateUserAction implements UpdateUser
{
    public function handle(User $user, array $data): bool
    {

        if (array_key_exists('phone_number', $data) && array_key_exists('phone_country_code', $data)) {
            $data['phone_number'] = phone($data['phone_number'], $data['phone_country_code']);
        }

        if (! isset($data['locale']) || ! in_array($data['locale'], Config::get('app.locales'))) {
            $data['locale'] = app()->getLocale();
        }

        if ($user->email != $data['email']) {
            $this->changeEmailWithSendNotification($user, $data['email']);
        }

        if (array_key_exists('password', $data)) {
            $this->removeTokensOfUser($user);
        }

        return $user->update(
            Arr::only(
                $data,
                [
                    'first_name',
                    'last_name',
                    'email',
                    'email_verified_at',
                    'phone_number',
                    'password',
                    'company_id',
                    'locale',
                    'is_active',
                ]
            )
        );
    }

    public function changeEmailWithSendNotification(User $user, $new_email): void
    {
        Mail::to($user->email)->send(new ChangeEmail($user, $new_email));
        $user->update(['email_verified_at' => null]);
        $this->removeTokensOfUser($user);
    }

    public function removeTokensOfUser(User $user): void
    {
        $user->tokens()->delete();
    }
}
