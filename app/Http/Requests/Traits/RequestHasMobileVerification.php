<?php

namespace App\Http\Requests\Traits;

use App\Exceptions\MobileVerification\InvalidMobileNumberException;
use App\Exceptions\MobileVerification\InvalidPersonIdException;
use App\Exceptions\MobileVerification\MobileNumberNotMatchedException;
use App\Exceptions\MobileVerification\PersonNotFoundException;
use App\Support\MobileVerification\Facades\MobileVerify;
use Illuminate\Validation\Validator;
use Propaganistas\LaravelPhone\PhoneNumber;

trait RequestHasMobileVerification
{
    protected $phoneNumber = 'phone_number';

    protected $phoneCountryCode = 'phone_country_code';

    protected $nationalId = 'national_id';

    /**
     * Configure the validator instance.
     *
     * @param  \Illuminate\Validation\Validator  $validator
     * @return void
     */
    public function withValidator(Validator $validator)
    {
        $validator->after(
            function ($validator) {
                if ($this->validated('national_id')) {
                    $this->checkMobileVerification($validator);
                }
            }
        );
    }

    private function checkMobileVerification($validator)
    {
        $errors = [];
        try {
            $phone = PhoneNumber::make($this->validated($this->phoneNumber), $this->validated($this->phoneCountryCode));
            MobileVerify::verify($phone, $this->validated($this->nationalId));
        } catch (MobileNumberNotMatchedException $e) {
            $errors['national_id'] = __('error.phone_number_not_matched');
        } catch (InvalidPersonIdException $e) {
            $errors['national_id'] = __('error.invalid_person_id');
        } catch (PersonNotFoundException $e) {
            $errors['national_id'] = __('error.person_id_not_found');
        } catch (InvalidMobileNumberException $e) {
            $errors['phone_number'] = __('error.invalid_mobile_number');
        }

        foreach ($errors as $key => $message) {
            $validator->errors()->add($key, $message);
        }
    }
}
