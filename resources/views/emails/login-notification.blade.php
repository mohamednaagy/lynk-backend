<x-mail::message>
 {{__('Hello')}},
 <br>
 <br>
 {{__('emails/login-notification.body')}}
 <br>
 <br>
 {{__('emails/login-notification.ip_address',['ip'=>$ipAddress])}}
 <br>
 {{__('emails/login-notification.at',['time'=>$timeLogin])}}
 @if ($browser )
 <br>
 {{__('emails/login-notification.browser',['browser'=>$browser])}}
 @endif
 @if ($device )
 <br>
 {{__('emails/login-notification.device',['device'=>$device])}}
 @endif
 @if ($platform)
 <br>
 {{__('emails/login-notification.platform',['platform'=>$platform])}}
 @endif
 <br>
</x-mail::message>
