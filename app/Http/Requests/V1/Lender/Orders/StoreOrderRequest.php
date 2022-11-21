<?php

namespace App\Http\Requests\V1\Lender\Orders;

use App\Exceptions\MobileVerification\InvalidMobileNumberException;
use App\Exceptions\MobileVerification\InvalidPersonIdException;
use App\Exceptions\MobileVerification\MobileNumberNotMatchedException;
use App\Exceptions\MobileVerification\PersonNotFoundException;
use App\Rules\ValidateSAID;
use App\Support\MobileVerification\Facades\MobileVerify;
use Illuminate\Foundation\Http\FormRequest;
use Propaganistas\LaravelPhone\PhoneNumber;

class StoreOrderRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        // __REVIEW__: Remove pls
        $tenant = tenant();

        //__REVIEW__ attributes need translation
        // See: https://laravel.com/docs/9.x/validation#specifying-attribute-in-language-files
        return [
            'reference_number' => ['nullable', 'string', 'max:100'],
            'national_id' => ['required', 'digits:10', new ValidateSAID],
            'phone_country_code' => ['required_with:phone_number', 'string', 'size:2'],
            // __REIVEW__: add "mobile" type to phone validation. See: https://github.com/Propaganistas/Laravel-Phone#validation
            'phone_number' => ['required', 'phone:phone_country_code', 'string'],
            'amount' => ['required', 'numeric', 'gt:0'],
            // __REVIEW__: selling price should be greater than or equal to "amount"
            'selling_price' => ['required', 'numeric', 'gt:0'],
        ];
    }

    /**
     * Configure the validator instance.
     *
     * @param  \Illuminate\Validation\Validator  $validator
     * @return void
     */
    public function withValidator($validator)
    {
        $validator->after(
            function ($validator) {
                $this->checkMobileVerification($validator);
            }
        );
    }

    private function checkMobileVerification($validator)
    {
        $errors = [];

        try {
            $phone = PhoneNumber::make($this->validated('phone_number'), $this->validated('phone_country_code'));
            MobileVerify::verify($phone, $this->validated('national_id'));
        } catch (MobileNumberNotMatchedException $e) {
            // __REVIEW__: move validation errors to "lang/ar/error.php" & "lang/en/error.php"
            $errors['national_id'] = __('validation.custom_validation.phone_number_not_matched');
        } catch (InvalidPersonIdException $e) {
            // __REVIEW__: move validation errors to "lang/ar/error.php" & "lang/en/error.php"
            $errors['national_id'] = __('validation.custom_validation.invalid_person_id');
        } catch (PersonNotFoundException $e) {
            // __REVIEW__: move validation errors to "lang/ar/error.php" & "lang/en/error.php"
            $errors['national_id'] = __('validation.custom_validation.person_id_not_found');
        } catch (InvalidMobileNumberException $e) {
            // __REVIEW__: move validation errors to "lang/ar/error.php" & "lang/en/error.php"
            $errors['phone_number'] = __('validation.custom_validation.invalid_mobile_number');
        }

        foreach ($errors as $key => $message) {
            $validator->errors()->add($key, $message);
        }
    }
}
