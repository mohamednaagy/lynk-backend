<x-mail::message>
{{ __('Dear :name', ['name' => $enquiry->name]) }},<br>

{{__('emails/visitor-enquiry.visitor_enquiry_message',
['name' => $enquiry->name, 'app_name' => config('app.name') ])}}

<x-mail::button :url="$url">
    {{ __('emails/visitor-enquiry.access_enquiry') }}
</x-mail::button>

</x-mail::message>
