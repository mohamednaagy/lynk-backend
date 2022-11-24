<x-mail::message>
 {{__('Hello')}},
 <br>
 {{__('emails/login-notification.body')}}
 <br>
 {{__('emails/login-notification.ip_address')}} {{$ipAddress}}
 <br>
 {{__('emails/login-notification.at')}} {{$timeLogin}}
 <br>
 {{__('emails/login-notification.browser')}} {{$browser}}
 @if ($device != null )
 <br>
 {{__('emails/login-notification.device')}} {{$device}}
 @endif
 @if ($platform != null)
 <br>
 {{__('emails/login-notification.platform')}} {{$platform}}
 @endif
 <br>

{{__('Thanks,')}} <br>
{{ config('app.name') }}
</x-mail::message>
