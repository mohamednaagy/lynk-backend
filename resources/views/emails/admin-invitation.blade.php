<x-mail::message>
{{ __('Dear :name', ['name' => $user->first_name]) }},<br>

{{__('emails/admin-invitation.complete_invitation_email_message',
['name' => Auth::user()->full_name, 'app_name' => config('app.name') ])}}

<x-mail::button :url="$url">
    {{ __('emails/admin-invitation.complete_registration') }}
</x-mail::button>

</x-mail::message>
