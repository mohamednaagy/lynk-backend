<x-mail::message>
{{ __('Please click the button below to verify your email address.') }}<br>

<x-mail::button :url="$url">
{{ __('Verify Email Address') }}
</x-mail::button>

{{ __('If you did not create an account, no further action is required.') }}.<br>

{{ __('Thanks,') }}<br>
{{ config('app.name') }}
</x-mail::message>
