<x-mail::message>
    {{ __('emails/change-email.dear', ['name' => $name]) }},<br>
    {{ __('emails/change-email.the_email_you_use_to_login_to_lynk_has_been_changed_by_an_administrator' , ['app_name' => config('app.name')]) }}<br>
    {{ __('emails/change-email.please_sign_in_again_using_the_button_below_to_confirm') }}.<br>

{{ __('Thanks,') }}<br>
{{ config('app.name') }}
</x-mail::message>
