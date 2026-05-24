<?php

namespace AchekConnect;

class OtpModule
{
    public function __construct(private HttpClient $http) {}

    /**
     * Send a WhatsApp OTP to a phone number.
     *
     * @param string      $phoneNumber     E.164 format e.g. "+2348XXXXXXXXX"
     * @param string|null $template        Custom message with {{code}} placeholder
     * @param string|null $recipientName   Replaces {{name}} in template
     * @param string|null $companyName     Replaces {{company}} in template
     * @param int|null    $senderNumberId  Specific WhatsApp number ID to send from
     * @param string|null $idempotencyKey  Reuse to safely retry without double-sending
     * @return array{requestId: string, expiresAt: string, message: string}
     *
     * @example
     *   $result    = $client->otp->send('+2348XXXXXXXXX');
     *   $requestId = $result['requestId'];
     */
    public function send(
        string  $phoneNumber,
        ?string $template       = null,
        ?string $recipientName  = null,
        ?string $companyName    = null,
        ?int    $senderNumberId = null,
        ?string $idempotencyKey = null,
    ): array {
        $body = ['phoneNumber' => $phoneNumber];
        if ($template)       $body['template']       = $template;
        if ($recipientName)  $body['recipientName']  = $recipientName;
        if ($companyName)    $body['companyName']    = $companyName;
        if ($senderNumberId) $body['senderNumberId'] = $senderNumberId;
        return $this->http->post('/otp/send', $body, $idempotencyKey);
    }

    /**
     * Verify the OTP code entered by the user.
     *
     * @return array{valid: bool, message: string}
     */
    public function verify(string $requestId, string $code): array
    {
        return $this->http->post('/otp/verify', [
            'requestId' => $requestId,
            'code'      => $code,
        ]);
    }

    /**
     * Fetch OTP delivery logs.
     */
    public function logs(int $limit = 50, int $offset = 0, ?string $status = null): array
    {
        $params = http_build_query(array_filter([
            'limit'  => $limit,
            'offset' => $offset,
            'status' => $status,
        ]));
        return $this->http->get('/otp/logs?' . $params);
    }
}
