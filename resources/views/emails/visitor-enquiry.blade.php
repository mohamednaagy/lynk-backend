<x-mail::message>
{{ __('Dear :name', ['name' => $enquiry->name]) }},<br>

{{__('emails/visitor-enquiry.visitor_enquiry_message')}}

<x-mail::panel>
    {{ __('emails/visitor-enquiry.enquiry_subject') }}
    {{ $enquiry->subject }}
</x-mail::panel>

<x-mail::button :url="$url">
    {{ __('emails/visitor-enquiry.access_enquiry') }}
</x-mail::button>

</x-mail::message>
