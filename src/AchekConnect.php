<?php

namespace AchekConnect;

/**
 * Achek Connect PHP SDK
 *
 * composer require achek/achek
 *
 * @example
 *   <?php
 *   require 'vendor/autoload.php';
 *
 *   use AchekConnect\AchekConnect;
 *
 *   $client = new AchekConnect('achek_live_xxxxxxxxxxxx');
 *
 *   // Send OTP
 *   $result    = $client->otp->send('+2348XXXXXXXXX');
 *   $requestId = $result['requestId'];
 *
 *   // Verify OTP
 *   $verification = $client->otp->verify($requestId, $userCode);
 *   if ($verification['valid']) {
 *       loginUser();
 *   }
 *
 *   // Transaction alert
 *   $client->alerts->transaction('+2348XXXXXXXXX', [
 *       'type'        => 'credit',
 *       'amount'      => 50000,
 *       'reference'   => 'TXN-9988',
 *       'accountName' => 'Chidi Okeke',
 *       'balance'     => 180000,
 *   ]);
 *
 *   // Verify incoming webhook
 *   $helper = new AchekWebhookHelper($_ENV['ACHEK_WEBHOOK_SECRET']);
 *   $sig    = $_SERVER['HTTP_X_ACHEK_SIGNATURE'] ?? '';
 *   if (!$helper->verify($sig, file_get_contents('php://input'))) {
 *       http_response_code(400);
 *       exit('Invalid signature');
 *   }
 *   $event = $helper->parse(file_get_contents('php://input'));
 */
class AchekConnect
{
    public readonly OtpModule        $otp;
    public readonly AlertsModule     $alerts;
    public readonly TicketsModule    $tickets;
    public readonly BroadcastsModule $broadcasts;
    public readonly EmailModule      $email;

    /**
     * @param string $apiKey         Your Achek Connect API key (starts with achek_)
     * @param string $baseUrl        Override the API base URL
     * @param int    $timeout        Request timeout in seconds (default: 15)
     * @param int    $maxAttempts    Total attempts per request (default: 3)
     * @param int    $initialDelayMs Initial retry back-off in ms (default: 500)
     */
    public function __construct(
        string $apiKey,
        string $baseUrl        = 'https://api.achek.com.ng',
        int    $timeout        = 15,
        int    $maxAttempts    = 3,
        int    $initialDelayMs = 500,
    ) {
        if (!$apiKey) {
            throw new \InvalidArgumentException('apiKey is required');
        }
        $http             = new HttpClient($apiKey, $baseUrl, $timeout, $maxAttempts, $initialDelayMs);
        $this->otp        = new OtpModule($http);
        $this->alerts     = new AlertsModule($http);
        $this->tickets    = new TicketsModule($http);
        $this->broadcasts = new BroadcastsModule($http);
        $this->email      = new EmailModule($http);
    }
}
