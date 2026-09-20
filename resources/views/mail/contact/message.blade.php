<p>New contact form message for {{ $shopName }}.</p>

<p>
    <strong>Name:</strong> {{ $contact->name }}<br>
    <strong>Email:</strong> {{ $contact->email }}
    @if ($contact->phone)
        <br><strong>Phone:</strong> {{ $contact->phone }}
    @endif
</p>

<p>{!! nl2br(e($contact->message)) !!}</p>
