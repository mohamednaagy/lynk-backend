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
        $tenant = tenant();

        return [
            'reference_number' => ['nullable', $tenant->unique('financing_orders', 'reference_number')],
            'national_id' => ['required', 'digits:10', new ValidateSAID],
            'phone_country_code' => ['required_with:phone_number', 'string', 'size:2'],
            'phone_number' => ['required', 'phone:phone_country_code', 'string'],
            'amount' => ['required', 'numeric'],
            'selling_price' => ['required', 'numeric'],
            'contract' => ['sometimes', 'file'],
            'power_of_attorney' => ['sometimes', 'file'],
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
            $errors['national_id'] = __('validation.custom_validation.phone_number_not_matched');
        } catch (InvalidPersonIdException $e) {
            $errors['national_id'] = __('validation.custom_validation.invalid_person_id');
        } catch (PersonNotFoundException $e) {
            $errors['national_id'] = __('validation.custom_validation.person_id_not_found');
        } catch (InvalidMobileNumberException $e) {
            $errors['phone_number'] = __('validation.custom_validation.invalid_mobile_number');
        }

        foreach ($errors as $key => $message) {
            $validator->errors()->add($key, $message);
        }
    }
}
