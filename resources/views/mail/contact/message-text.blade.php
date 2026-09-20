New contact form message for {{ $shopName }}.

Name: {{ $contact->name }}
Email: {{ $contact->email }}
@if ($contact->phone)
Phone: {{ $contact->phone }}
@endif

{{ $contact->message }}
