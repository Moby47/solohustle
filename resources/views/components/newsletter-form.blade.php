@props(['source' => 'site', 'dark' => false])

{{-- Newsletter sign-up: plain form (works without JavaScript), honeypot + throttle on the server. --}}
@php($formId = 'newsletter-'.$source)

@if (session('newsletter_status') && session('newsletter_form') === $formId)
    <p role="status" {{ $attributes->class(['rounded-lg px-4 py-3 text-sm font-medium', 'bg-white/15 text-white' => $dark, 'bg-primary-light text-primary' => ! $dark]) }}>
        {{ session('newsletter_status') }}
    </p>
@else
    <form method="POST" action="{{ route('newsletter.subscribe') }}#{{ $formId }}" id="{{ $formId }}" {{ $attributes->class('text-left') }}>
        @csrf
        <x-honeypot />
        <input type="hidden" name="source" value="{{ $source }}">
        <input type="hidden" name="form_id" value="{{ $formId }}">
        <div class="flex flex-col gap-2 sm:flex-row">
            <label for="{{ $formId }}-email" class="sr-only">Email address</label>
            <input id="{{ $formId }}-email" type="email" name="email" required autocomplete="email" placeholder="you@example.com"
                   value="{{ session('newsletter_form') === $formId ? old('email') : '' }}"
                   class="block w-full rounded-md border border-line bg-white px-4 py-3 text-ink placeholder:text-muted focus:border-primary focus:outline-none">
            <button type="submit" class="shrink-0 rounded-md bg-primary px-5 py-3 font-semibold text-white hover:bg-primary-dark">Subscribe</button>
        </div>
        @if (session('newsletter_form') === $formId)
            @error('email')
                <p class="mt-2 text-sm text-red-700">{{ $message }}</p>
            @enderror
        @endif
        <p @class(['mt-2 text-xs', 'text-white/75' => $dark, 'text-muted' => ! $dark])>We'll email you a link to confirm. Unsubscribe any time.</p>
    </form>
@endif
