<x-mail::message>
    {{ __('Dear :name', ['name' => $enquiry->name]) }},<br>

    {{-- __REVIEW__ enquiry_reply_message doesn't have app_name or name variables!!! --}}
    {{__('emails/visitor-enquiry.enquiry_reply_message',
    ['name' => $enquiry->name, 'app_name' => config('app.name') ])}}

    {{-- __REVIEW__ we need to add a section here. It is Enquiry Subject (موضوع الاستفسار)  --}}
    {{-- __REVIEW__ subject content will be inside panel https://laravel.com/docs/9.x/mail#panel-component --}}

    {{-- __REVIEW__ we need to add a section here. It is Answer (الإجابة)  --}}
    {{-- __REVIEW__ reply content will be inside panel https://laravel.com/docs/9.x/mail#panel-component --}}

    <x-mail::button :url="$url">
        {{ __('emails/visitor-enquiry.access_enquiry') }}
    </x-mail::button>

</x-mail::message>
