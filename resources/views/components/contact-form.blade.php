{{-- The contact form, shown on the Contact page. Works without JavaScript. --}}
@php
    $inputClass = 'mt-1 block w-full rounded-md border bg-white px-3 py-2.5 text-ink placeholder:text-muted focus:border-primary focus:outline-none';
@endphp

<section id="contact-form" aria-labelledby="contact-form-title" class="mt-12 rounded-2xl border border-line bg-surface p-6 sm:p-8">
    <h2 id="contact-form-title" class="text-2xl">Send us a message</h2>

    @if (session('contact_status'))
        <p role="status" class="mt-4 rounded-lg bg-primary-light px-4 py-3 font-medium text-primary">{{ session('contact_status') }}</p>
    @else
        @if ($errors->any())
            <p role="alert" class="mt-4 rounded-lg bg-red-50 px-4 py-3 text-sm text-red-800">Please check the highlighted fields below.</p>
        @endif

        <form method="POST" action="{{ route('contact.store') }}" class="mt-6 space-y-4" novalidate>
            @csrf
            <x-honeypot />

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="contact-name" class="text-sm font-medium text-ink">Your name</label>
                    <input id="contact-name" name="name" type="text" value="{{ old('name') }}" required autocomplete="name"
                           @error('name') aria-invalid="true" aria-describedby="contact-name-error" @enderror
                           @class([$inputClass, 'border-red-500' => $errors->has('name'), 'border-line' => ! $errors->has('name')])>
                    @error('name') <p id="contact-name-error" class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="contact-email" class="text-sm font-medium text-ink">Email address</label>
                    <input id="contact-email" name="email" type="email" value="{{ old('email') }}" required autocomplete="email"
                           @error('email') aria-invalid="true" aria-describedby="contact-email-error" @enderror
                           @class([$inputClass, 'border-red-500' => $errors->has('email'), 'border-line' => ! $errors->has('email')])>
                    @error('email') <p id="contact-email-error" class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
                </div>
            </div>

            <div>
                <label for="contact-subject" class="text-sm font-medium text-ink">Subject</label>
                <input id="contact-subject" name="subject" type="text" value="{{ old('subject') }}" required
                       @error('subject') aria-invalid="true" aria-describedby="contact-subject-error" @enderror
                       @class([$inputClass, 'border-red-500' => $errors->has('subject'), 'border-line' => ! $errors->has('subject')])>
                @error('subject') <p id="contact-subject-error" class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="contact-message" class="text-sm font-medium text-ink">Message</label>
                <textarea id="contact-message" name="message" rows="6" required
                          @error('message') aria-invalid="true" aria-describedby="contact-message-error" @enderror
                          @class([$inputClass, 'border-red-500' => $errors->has('message'), 'border-line' => ! $errors->has('message')])>{{ old('message') }}</textarea>
                @error('message') <p id="contact-message-error" class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
            </div>

            <button type="submit" class="rounded-md bg-primary px-6 py-3 font-semibold text-white hover:bg-primary-dark">Send message</button>
        </form>
    @endif
</section>
