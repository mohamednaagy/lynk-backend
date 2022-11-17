<?php

namespace App\Transformers;

use League\Fractal\Resource\Primitive;
use League\Fractal\TransformerAbstract;
use Spatie\LaravelSettings\Settings;

class LenderSettingsTransformer extends TransformerAbstract
{
    protected array $defaultIncludes = [];

    protected array $availableIncludes = [
        'email_verification_enabled',
    ];

    public function transform(Settings $settings): array
    {
        return [

        ];
    }

    public function includeEmailVerificationEnabled(Settings $settings): Primitive
    {
        return $this->primitive($settings->email_verification_enabled);
    }
}
