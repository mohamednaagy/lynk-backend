<x-mail::message>
 {{__('Hello')}},
 <br>
 {{__('emails/login-notification.body')}}
 <br>
 {{__('emails/login-notification.ip_address',['ip'=>$ipAddress])}}
 <br>
 {{__('emails/login-notification.at',['time'=>$timeLogin])}}
 <br>
 {{__('emails/login-notification.browser',['browser'=>$browser])}}
 @if ($device != null )
 <br>
 {{__('emails/login-notification.device',['device'=>$device])}}
 @endif
 @if ($platform != null)
 <br>
 {{__('emails/login-notification.platform',['platform'=>$platform])}}
 @endif
 <br>

{{__('Thanks,')}} <br>
{{ config('app.name') }}
</x-mail::message>
