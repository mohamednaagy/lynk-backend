<x-mail::message>
# {{ trans('otpify::email.greeting', ['name' => $notifiable->fullName]) }}

{{ trans('otpify::email.thanks_security') }}

{{ trans('otpify::email.enter_otp') }}

{{ trans('otpify::email.expire_at', ['time' => config('otpify.drivers.email.code_expiration_time', 5)]) }}

<p style="text-align: center;" dir="ltr"><b>{{ implode(' ', str_split($otpCode)) }}</b></p>

<br>
<br>
</x-mail::message>
