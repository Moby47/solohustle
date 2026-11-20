<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

/** The small part of Brevo's API the newsletter needs. Everything is optional: without an API key and list it does nothing. */
final class Brevo
{
    private const API = 'https://api.brevo.com/v3';

    public function __construct(private readonly Settings $settings) {}

    public function isConfigured(): bool
    {
        return filled($this->settings->secret('integrations.brevo_api_key'))
            && filled($this->settings->get('integrations.brevo_list_id'));
    }

    /**
     * Checks the API key and returns the Brevo account's email, for a friendly "Connected as …" message.
     */
    public function testConnection(): string
    {
        $account = $this->request()->get('/account')->throw()->json();

        return (string) ($account['email'] ?? $account['companyName'] ?? 'your Brevo account');
    }

    /**
     * Adds the contact to the list, or updates them if they already exist.
     */
    public function subscribe(string $email, ?string $name): void
    {
        $this->request()->post('/contacts', array_filter([
            'email' => $email,
            'attributes' => $name ? ['FIRSTNAME' => $name] : null,
            'listIds' => [$this->listId()],
            'updateEnabled' => true,
        ]))->throw();
    }

    public function unsubscribe(string $email): void
    {
        $response = $this->request()->post("/contacts/lists/{$this->listId()}/contacts/remove", ['emails' => [$email]]);

        // Not on the list (never synced, or removed in Brevo already) is fine.
        if ($response->failed() && $response->status() !== 400 && $response->status() !== 404) {
            $response->throw();
        }
    }

    private function listId(): int
    {
        return (int) $this->settings->get('integrations.brevo_list_id');
    }

    private function request(): PendingRequest
    {
        return Http::baseUrl(self::API)
            ->withHeaders(['api-key' => (string) $this->settings->secret('integrations.brevo_api_key')])
            ->acceptJson()
            ->timeout(15);
    }
}
