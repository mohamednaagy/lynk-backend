@component('mail::message')
# {{ __('You are Invited') }}

{{ __('Dear :name', ['name' => $user->first_name]) }},<br>

{{__('emails/invitation-complete-register.complete_invitation_email_message',
['name' => Auth::user()->full_name, 'app_name' => config('app.name') ])}}



@component('mail::button', ['url' => $url])
{{ __('Complete Register') }}
@endcomponent

@endcomponent
