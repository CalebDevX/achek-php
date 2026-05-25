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
 *   $client = new AchekConnect('watp_live_xxxxxxxxxxxx');
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
 */
class AchekConnect
{
    public readonly OtpModule        $otp;
    public readonly AlertsModule     $alerts;
    public readonly TicketsModule    $tickets;
    public readonly BroadcastsModule $broadcasts;

    /**
     * @param string $apiKey  Your Achek Connect API key (starts with watp_)
     * @param string $baseUrl Override the API base URL
     * @param int    $timeout Request timeout in seconds
     */
    public function __construct(
        string $apiKey,
        string $baseUrl = 'https://verify.achek.com.ng',
        int    $timeout = 15,
    ) {
        if (!$apiKey) {
            throw new \InvalidArgumentException('apiKey is required');
        }
        $http             = new HttpClient($apiKey, $baseUrl, $timeout);
        $this->otp        = new OtpModule($http);
        $this->alerts     = new AlertsModule($http);
        $this->tickets    = new TicketsModule($http);
        $this->broadcasts = new BroadcastsModule($http);
    }
}
