<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Subscriber;
use App\Support\Newsletter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

/** Public newsletter sign-up, confirmation and unsubscribe pages. */
class NewsletterController extends Controller
{
    public function subscribe(Request $request, Newsletter $newsletter): RedirectResponse
    {
        // Several sign-up forms can be on one page; remember which one was used so the message shows in the right place.
        $formId = preg_replace('/[^a-z0-9-]/', '', (string) $request->input('form_id')) ?: 'newsletter-site';
        $back = $this->safePreviousUrl().'#'.$formId;

        $validator = Validator::make($request->all(), [
            'email' => ['required', 'email', 'max:150'],
            'name' => ['nullable', 'string', 'max:100'],
            'source' => ['nullable', 'string', 'max:50'],
        ], [
            'email.required' => 'Please enter your email address.',
            'email.email' => 'That email address doesn\'t look right.',
        ]);

        if ($validator->fails()) {
            return redirect()->to($back)->withErrors($validator)->withInput()->with('newsletter_form', $formId);
        }

        $newsletter->subscribe($request->input('email'), $request->input('name'), $request->input('source'));

        // The same reply for new and existing addresses, so the form can't be used to check who is subscribed.
        return redirect()->to($back)
            ->with('newsletter_form', $formId)
            ->with('newsletter_status', 'Almost done! Check your inbox and click the link to confirm your subscription.');
    }

    /**
     * The page the form was on. The browser's "previous page" can name another website, so only accept this one.
     */
    private function safePreviousUrl(): string
    {
        $previous = strtok(url()->previous(), '#');

        return parse_url($previous, PHP_URL_HOST) === parse_url(config('app.url'), PHP_URL_HOST) ? $previous : url('/');
    }

    public function confirm(Request $request, Subscriber $subscriber, Newsletter $newsletter): View
    {
        if (! $request->hasValidSignature()) {
            return view('newsletter.message', [
                'title' => 'This link has expired',
                'text' => 'Confirmation links work for '.Subscriber::CONFIRM_LINK_DAYS.' days. Please sign up again and we\'ll send you a fresh one.',
                'showForm' => true,
            ]);
        }

        $newsletter->confirm($subscriber);

        return view('newsletter.message', [
            'title' => $subscriber->unsubscribed_at ? 'You\'re unsubscribed' : 'You\'re subscribed!',
            'text' => $subscriber->unsubscribed_at
                ? 'You unsubscribed earlier, so we won\'t email you. You can sign up again any time.'
                : 'Thanks for confirming. Look out for new jobs and guides in your inbox.',
            'showForm' => false,
        ]);
    }

    public function unsubscribe(Request $request, Subscriber $subscriber, Newsletter $newsletter): View
    {
        abort_unless($request->hasValidSignature(), 404);

        $newsletter->unsubscribe($subscriber);

        return view('newsletter.message', [
            'title' => 'You\'ve been unsubscribed',
            'text' => 'You won\'t get any more emails from us. Changed your mind? You can sign up again below.',
            'showForm' => true,
        ]);
    }
}
