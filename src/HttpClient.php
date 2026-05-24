<?php

namespace AchekConnect;

class HttpClient
{
    private string $apiKey;
    private string $baseUrl;
    private int    $timeout;
    private int    $maxAttempts;
    private int    $initialDelayMs;

    /** HTTP status codes that are safe to retry */
    private const RETRYABLE = [429, 500, 502, 503, 504];

    public function __construct(
        string $apiKey,
        string $baseUrl,
        int    $timeout,
        int    $maxAttempts    = 3,
        int    $initialDelayMs = 500,
    ) {
        $this->apiKey         = $apiKey;
        $this->baseUrl        = rtrim($baseUrl, '/');
        $this->timeout        = $timeout;
        $this->maxAttempts    = max(1, $maxAttempts);
        $this->initialDelayMs = $initialDelayMs;
    }

    /**
     * Execute an HTTP request, retrying on transient errors with exponential back-off.
     *
     * @param  string      $method  HTTP verb (GET, POST, PATCH, PUT, DELETE)
     * @param  string      $path    API path, e.g. "/otp/send"
     * @param  array|null  $body    Request body (JSON-serialised)
     * @param  string|null $idempotencyKey  Optional idempotency key header value
     * @return array                Decoded response body
     * @throws AchekConnectException
     */
    public function request(
        string  $method,
        string  $path,
        ?array  $body           = null,
        ?string $idempotencyKey = null,
    ): array {
        $url     = $this->baseUrl . '/api' . $path;
        $payload = $body !== null ? json_encode($body, JSON_THROW_ON_ERROR) : null;

        $headers = [
            'Content-Type: application/json',
            'X-API-Key: ' . $this->apiKey,
            'Accept: application/json',
            'User-Agent: achekconnect-php/2.0.0',
        ];
        if ($idempotencyKey !== null) {
            $headers[] = 'Idempotency-Key: ' . $idempotencyKey;
        }

        $lastException = null;

        for ($attempt = 1; $attempt <= $this->maxAttempts; $attempt++) {
            [$data, $statusCode] = $this->curlRequest($method, $url, $headers, $payload);

            if ($statusCode < 400) {
                return $data;
            }

            $lastException = new AchekConnectException(
                $data['error'] ?? "Request failed with status {$statusCode}",
                $statusCode,
                $data['code'] ?? null,
            );

            // Only retry on transient errors and if we have attempts left
            if (!in_array($statusCode, self::RETRYABLE, true) || $attempt >= $this->maxAttempts) {
                throw $lastException;
            }

            // Exponential back-off: 500 ms, 1 000 ms, 2 000 ms …
            $delayUs = $this->initialDelayMs * (2 ** ($attempt - 1)) * 1000;
            usleep((int) $delayUs);
        }

        throw $lastException ?? new AchekConnectException('Max retries exceeded');
    }

    /**
     * Execute a single cURL request.
     *
     * @return array{0: array, 1: int}  [decoded response body, HTTP status code]
     * @throws AchekConnectException on network failure
     */
    private function curlRequest(
        string  $method,
        string  $url,
        array   $headers,
        ?string $payload,
    ): array {
        $ch = curl_init();

        curl_setopt_array($ch, [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => $this->timeout,
            CURLOPT_CONNECTTIMEOUT => min($this->timeout, 10),
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_CUSTOMREQUEST  => strtoupper($method),
        ]);

        if ($payload !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        }

        $raw    = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error  = curl_error($ch);
        curl_close($ch);

        if ($raw === false || $error !== '') {
            throw new AchekConnectException('Network error: ' . ($error ?: 'cURL request failed'));
        }

        $data = json_decode((string) $raw, true, 512, JSON_THROW_ON_ERROR);
        return [$data, $status];
    }

    public function get(string $path): array
    {
        return $this->request('GET', $path);
    }

    public function post(string $path, ?array $body = null, ?string $idempotencyKey = null): array
    {
        return $this->request('POST', $path, $body, $idempotencyKey);
    }

    public function patch(string $path, ?array $body = null): array
    {
        return $this->request('PATCH', $path, $body);
    }

    public function put(string $path, ?array $body = null): array
    {
        return $this->request('PUT', $path, $body);
    }

    public function delete(string $path): array
    {
        return $this->request('DELETE', $path);
    }
}
