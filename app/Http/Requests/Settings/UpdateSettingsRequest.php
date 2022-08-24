<?php

namespace App\Http\Requests\Settings;

use App\Enums\Area;
use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;
use function Symfony\Component\String\match;
use App\Actions\Contracts\GetSettingsRequestRule;

class UpdateSettingsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return array_merge([
            'area' => ['required', Rule::in(Area::getValues())]
        ], $this->getCustomRulesByArea($this->area));
    }

    private function getCustomRulesByArea(string $area): array
    {
        return match ($area) {
            'General' => [
                'default_otp_driver' => ['required', 'string', Rule::in(\Otpify::getOtpifyDrivers())]
            ],
            Area::SuperAdmin => [
                'otp_driver' => ['required', 'string', Rule::in(\Otpify::getOtpifyDrivers())],
                'otp_enabled' => ['required', 'boolean']
            ],
            Area::Customer => [
                'otp_driver' => ['required', 'string', Rule::in(\Otpify::getOtpifyDrivers())],
                'otp_enabled' => ['required', 'boolean']
            ],
        };
    }

}
