<x-mail::message>
{{ __('Dear :name', ['name' => $invitee->first_name]) }},<br>

{{__('emails/admin-invitation.complete_invitation_email_message',
['name' => $inviter->full_name, 'app_name' => config('app.name') ])}}

<x-mail::button :url="$url">
    {{ __('emails/admin-invitation.complete_registration') }}
</x-mail::button>

</x-mail::message>
