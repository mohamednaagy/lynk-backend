<x-mail::message>


<x-mail::button :url="''">
{{_('emails/trader-order-stopped.body',
['order_id' => $this->traderOrder->id],
)}}
{{_(['next_step' => $nextStepNode->step])}}
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
