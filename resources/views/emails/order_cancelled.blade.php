<x-mail::message>

    {{ __('Dear Admin') }},<br>

    <x-mail::panel>
        {{$notification->order_id}} order Canceled
    </x-mail::panel>
    {{ __('Thanks,') }}<br>
    {{ config('app.name') }}

</x-mail::message>
