<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\ContactRequest;
use App\Mail\ContactMessageReceived;
use App\Models\ContactMessage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Mail;
use Throwable;

/** Saves contact-form messages and forwards them to the owner by email. */
class ContactController extends Controller
{
    public function store(ContactRequest $request): RedirectResponse
    {
        $message = ContactMessage::create($request->validated());

        // The message is safe in the Inbox either way; a mail problem shouldn't show the visitor an error.
        try {
            Mail::to(setting('general.contact_recipient'))->queue(new ContactMessageReceived($message));
        } catch (Throwable $exception) {
            report($exception);
        }

        return redirect()->to(url('/contact').'#contact-form')
            ->with('contact_status', 'Thanks, '.$message->name.'! Your message has been sent. We usually reply within two working days.');
    }
}
