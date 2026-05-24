<?php

namespace AchekConnect;

/**
 * Webhook signature verification and parsing for Achek Connect.
 *
 * Achek signs every webhook delivery with HMAC-SHA256 using your webhook
 * secret. The signature is sent in the ``X-Achek-Signature`` HTTP header as
 * ``sha256=<hex-digest>``.
 *
 * @example
 *   <?php
 *   use AchekConnect\AchekWebhookHelper;
 *
 *   $helper = new AchekWebhookHelper($_ENV['ACHEK_WEBHOOK_SECRET']);
 *   $sig    = $_SERVER['HTTP_X_ACHEK_SIGNATURE'] ?? '';
 *   $body   = file_get_contents('php://input');
 *
 *   if (!$helper->verify($sig, $body)) {
 *       http_response_code(400);
 *       exit('Invalid webhook signature');
 *   }
 *
 *   $event = $helper->parse($body);
 *   echo $event['event']; // e.g. "otp.verified", "handoff.requested"
 *
 *   http_response_code(200);
 */
class AchekWebhookHelper
{
    private string $secret;

    /**
     * @param string $webhookSecret  The webhook secret shown in your Achek dashboard.
     * @throws \InvalidArgumentException if the secret is empty.
     */
    public function __construct(string $webhookSecret)
    {
        if ($webhookSecret === '') {
            throw new \InvalidArgumentException('webhookSecret is required');
        }
        $this->secret = $webhookSecret;
    }

    /**
     * Verify the HMAC-SHA256 signature from the ``X-Achek-Signature`` header.
     *
     * Uses ``hash_equals`` to prevent timing attacks.
     *
     * @param string $signature  Value of the ``X-Achek-Signature`` header
     *                           (e.g. ``"sha256=abc123..."``).
     * @param string $payload    Raw request body.
     * @return bool              ``true`` if the signature is valid.
     */
    public function verify(string $signature, string $payload): bool
    {
        try {
            $sig      = preg_replace('/^sha256=/', '', $signature);
            $expected = hash_hmac('sha256', $payload, $this->secret);
            return hash_equals($expected, $sig);
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * Decode and return the webhook event as an associative array.
     *
     * @param  string $payload  Raw request body.
     * @return array            Parsed event with at least ``event`` and ``timestamp`` keys.
     * @throws \JsonException   On malformed JSON.
     */
    public function parse(string $payload): array
    {
        return json_decode($payload, true, 512, JSON_THROW_ON_ERROR);
    }
}
