@props(['headings'])

<ol {{ $attributes->class('space-y-2 text-sm') }}>
    @foreach ($headings as $heading)
        <li @class(['pl-4' => $heading['level'] === 3])>
            <a href="#{{ $heading['id'] }}" class="text-muted no-underline hover:text-primary">{{ $heading['text'] }}</a>
        </li>
    @endforeach
</ol>
