<?php

namespace AchekConnect;

/**
 * Send transactional emails via Achek's configured SMTP.
 *
 * The sender address and domain are configured in your Achek dashboard.
 *
 * @example
 *   $client->email->send(
 *       to:      'customer@example.com',
 *       subject: 'Your OTP code',
 *       html:    '<p>Your code is <strong>847293</strong>. Valid for 10 minutes.</p>',
 *   );
 */
class EmailModule
{
    public function __construct(private HttpClient $http) {}

    /**
     * Send a transactional email.
     *
     * Supply ``$text``, ``$html``, or both. When both are supplied, HTML takes
     * precedence in mail clients that support it; plain text is used as fallback.
     *
     * @param string      $to       Recipient email address
     * @param string      $subject  Email subject line
     * @param string|null $text     Plain-text body
     * @param string|null $html     HTML body (takes precedence over $text)
     * @param string|null $fromName Sender display name override
     * @return array{messageId: string, to: string, status: string}
     */
    public function send(
        string  $to,
        string  $subject,
        ?string $text     = null,
        ?string $html     = null,
        ?string $fromName = null,
    ): array {
        $body = ['to' => $to, 'subject' => $subject];
        if ($text     !== null) $body['text']     = $text;
        if ($html     !== null) $body['html']     = $html;
        if ($fromName !== null) $body['fromName'] = $fromName;
        return $this->http->post('/email/send', $body);
    }
}
