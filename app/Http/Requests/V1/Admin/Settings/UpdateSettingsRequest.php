<?php

namespace App\Http\Requests\V1\Admin\Settings;

use App\Enums\Area;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Otpify\Facades\Otpify;

/**
 * @property string $area
 */
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
            'area' => ['required', Rule::in(Area::getValues())],
        ], $this->getCustomRulesByArea($this->area));
    }

    private function getCustomRulesByArea(string $area = null): array
    {
        return match ($area) {
            'General' => [
                'otp_driver' => ['required', 'string', Rule::in(Otpify::getOtpifyDrivers())],
            ],
            Area::SuperAdmin => [
                'otp_driver' => ['required', 'string', Rule::in(Otpify::getOtpifyDrivers())],
                'otp_enabled' => ['required', 'boolean'],
            ],
            Area::Customer => [
                'otp_driver' => ['required', 'string', Rule::in(Otpify::getOtpifyDrivers())],
                'otp_enabled' => ['required', 'boolean'],
            ],
            default => []
        };
    }
}
