<x-mail::message>
{{ __('Dear :name', ['name' => $enquiry->name]) }},<br>

{{-- __REVIEW__ there should not be "name" or "app_name" since it doesn't exist in the translation !!! --}}
{{__('emails/visitor-enquiry.visitor_enquiry_message',
['name' => $enquiry->name, 'app_name' => config('app.name') ])}}

{{-- __REVIEW__ we need to add a section before URL. It is Enquiry Subject (موضوع الاستفسار)  --}}
{{-- __REVIEW__ subject content will be inside panel https://laravel.com/docs/9.x/mail#panel-component --}}

<x-mail::button :url="$url">
    {{ __('emails/visitor-enquiry.access_enquiry') }}
</x-mail::button>

</x-mail::message>
