<x-mail::message>
    {{ __('emails/wallet-remaining-balance.greeting', ['company_name' => $company_name]) }},
    <br>
    <br>
        {{ __('emails/wallet-remaining-balance.body' , ['balance' => $balance , 'remaining_balance_limit' => $remaining_balance_limit]) }}
    <br>
    <br>
        {{ __('emails/wallet-remaining-balance.footer' ) }}
    <br>
    <br>
    {{ __('emails/wallet-remaining-balance.regards' ) }}
    <br>
    {{config('app.name')}}

</x-mail::message>
