<?php

namespace App\Actions\Auth;

use App\Actions\Contracts\Auth\UpdateMyProfile;
use App\Actions\Contracts\GetSettingsClassInstance;
use App\Actions\Contracts\UpdateUser;
use App\Models\User;

class UpdateMyProfileAction implements UpdateMyProfile
{
    public function __construct(
        protected UpdateUser $updateUser,
        protected GetSettingsClassInstance $getSettingsClassInstance
    ) {
    }

    public function handle(User $user, $data, string $area): User
    {
        $this->updateUser->handle($user, $data);

        if ($data['email'] !== $user->email && $this->isEmailVerifiedRequired($area)) {
            $user->forceFill(['email_verified_at' => null])->save();
            $user->sendEmailVerificationNotification();
        }

        return $user;
    }

    private function isEmailVerifiedRequired($area)
    {
        $setting = $this->getSettingsClassInstance->handle($area);

        return isset($setting->email_verification_enabled) && (bool) $setting->email_verification_enabled;
    }
}
