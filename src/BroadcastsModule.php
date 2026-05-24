<?php

namespace AchekConnect;

class BroadcastsModule
{
    public function __construct(private HttpClient $http) {}

    /**
     * Send a WhatsApp broadcast to up to 1,000 recipients.
     *
     * @param string   $name       Display name for this broadcast
     * @param string   $message    WhatsApp markdown supported
     * @param string[] $recipients E.164 phone numbers
     */
    public function send(string $name, string $message, array $recipients): array
    {
        return $this->http->post('/broadcasts', [
            'name'       => $name,
            'message'    => $message,
            'recipients' => $recipients,
        ]);
    }

    /** List recent broadcasts. */
    public function list(): array
    {
        return $this->http->get('/broadcasts');
    }

    /** Get delivery status of a broadcast. */
    public function status(int $broadcastId): array
    {
        return $this->http->get("/broadcasts/{$broadcastId}");
    }
}
