@component('mail::message')
# {{ __('You are Invited') }}

{{ __('Dear :name', ['name' => $user->name]) }},<br>

{{ __(':name has invited you to join to :app_name .',
['name' => $user->full_name, 'app_name' => config('app.name') ]) }}


@component('mail::button', ['url' => $url])
{{ __('Complete Register') }}
@endcomponent

@endcomponent
