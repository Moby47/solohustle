<x-mail::message>
# New message from {{ $contactMessage->name }}

**From:** {{ $contactMessage->name }} ({{ $contactMessage->email }})<br>
**Subject:** {{ $contactMessage->subject }}

<x-mail::panel>
{{ $contactMessage->message }}
</x-mail::panel>

Just reply to this email to answer {{ $contactMessage->name }} directly.

<x-mail::button :url="$inboxUrl">
Open in your inbox
</x-mail::button>
</x-mail::message>
