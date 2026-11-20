{{-- Text logo until the owner uploads one in Settings (B3). --}}
<a href="{{ route('home') }}" {{ $attributes->class('inline-flex items-baseline gap-0.5 text-xl font-extrabold tracking-tight text-primary no-underline') }}>
    <span>{{ config('app.name') }}</span><span class="text-accent" aria-hidden="true">.</span>
</a>
