<x-mail::message>
# New message from {{ $contactMessage->name }}

**From:** {{ $contactMessage->name }} ({{ $contactMessage->email }})<br>
**Subject:** {{ $contactMessage->subject }}

<x-mail::panel>
{{-- Visitors' text is shown as typed: Markdown characters are escaped so nobody can slip a disguised link into your inbox. --}}
{{ preg_replace('/([\\\\`*_{}\[\]()#+\-.!|>~])/', '\\\\$1', $contactMessage->message) }}
</x-mail::panel>

Just reply to this email to answer {{ $contactMessage->name }} directly.

<x-mail::button :url="$inboxUrl">
Open in your inbox
</x-mail::button>
</x-mail::message>
