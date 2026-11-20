<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Support\Facades\Mail;

/** Points Laravel's mailer at the SMTP details saved in Settings; without them the .env mailer is used. */
final class MailSettings
{
    public function __construct(private readonly Settings $settings) {}

    public function apply(): void
    {
        config([
            'mail.from.name' => $this->settings->get('integrations.mail_from_name')
                ?: $this->settings->get('general.site_name'),
        ]);

        if (filled($fromAddress = $this->settings->get('integrations.mail_from_address'))) {
            config(['mail.from.address' => $fromAddress]);
        }

        if (blank($host = $this->settings->get('integrations.smtp_host'))) {
            return;
        }

        config([
            'mail.default' => 'smtp',
            'mail.mailers.smtp.host' => $host,
            'mail.mailers.smtp.port' => (int) $this->settings->get('integrations.smtp_port', 587),
            'mail.mailers.smtp.username' => $this->settings->get('integrations.smtp_username'),
            'mail.mailers.smtp.password' => $this->settings->secret('integrations.smtp_password'),
        ]);

        // Drop any mailer already built with the old details.
        Mail::purge('smtp');
    }
}
