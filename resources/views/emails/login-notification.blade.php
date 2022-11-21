<x-mail::message>
 {{__('emails/login-notification.greet')}}
 <br>
 {{__('emails/login-notification.body')}}
 <br>
 {{__('emails/login-notification.ip-address')}}
 {{$ipAddress}}
 <br>
 {{__('emails/login-notification.at')}}{{$timeLogin}}
 <br>
 {{__('emails/login-notification.device')}}{{$device}}
 <br>
 {{__('emails/login-notification.platform')}}{{$platform}}
 <br>
 {{__('emails/login-notification.browser')}}{{$browser}}

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
