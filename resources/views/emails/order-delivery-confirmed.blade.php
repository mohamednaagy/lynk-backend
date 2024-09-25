<x-mail::message>
    {{ __('Dear :name', ['name' => $name]) }},
    <br>
    <br>
        {{ __('emails/order-delivery-confirmed.body' , ['company_name' => $company_name , 'order_id' => $order_id , 'trader_order_reference' => $trader_reference]) }}
    <br>
    <br>
        {{ __('emails/order-delivery-confirmed.footer' ) }}
    <br>
    <br>
    {{ __('emails/order-delivery-confirmed.regards' ) }}
    <br>
    {{config('app.name')}}

</x-mail::message>
