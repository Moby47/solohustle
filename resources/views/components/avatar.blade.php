@props(['user', 'size' => 40])

@php($photo = media_url($user->photo))

@if ($photo)
    <img src="{{ $photo }}" alt="" width="{{ $size }}" height="{{ $size }}" loading="lazy"
         {{ $attributes->class('shrink-0 rounded-full object-cover') }} style="width: {{ $size }}px; height: {{ $size }}px">
@else
    {{-- Initials on brand lavender when there's no photo. --}}
    <span aria-hidden="true" {{ $attributes->class('inline-flex shrink-0 items-center justify-center rounded-full bg-secondary-light font-semibold text-primary') }}
          style="width: {{ $size }}px; height: {{ $size }}px; font-size: {{ max(12, (int) round($size / 2.6)) }}px">
        {{ str($user->name)->explode(' ')->take(2)->map(fn ($part) => mb_substr($part, 0, 1))->implode('') }}
    </span>
@endif
