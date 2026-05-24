<?php

namespace AchekConnect;

class TicketsModule
{
    public function __construct(private HttpClient $http) {}

    /**
     * Create a support ticket. Optionally notify the customer via WhatsApp.
     */
    public function create(
        string  $phoneNumber,
        string  $subject,
        ?string $description          = null,
        string  $priority             = 'normal',
        bool    $notifyCustomer       = true,
        ?string $notificationMessage  = null,
        array   $metadata             = []
    ): array {
        $body = [
            'phoneNumber'    => $phoneNumber,
            'subject'        => $subject,
            'priority'       => $priority,
            'notifyCustomer' => $notifyCustomer,
        ];
        if ($description)         $body['description']         = $description;
        if ($notificationMessage) $body['notificationMessage'] = $notificationMessage;
        if ($metadata)            $body['metadata']            = $metadata;
        return $this->http->post('/tickets', $body);
    }

    /** List all tickets. */
    public function list(?string $status = null, int $limit = 50): array
    {
        $params = http_build_query(array_filter(['status' => $status, 'limit' => $limit]));
        return $this->http->get('/tickets?' . $params);
    }

    /** Get a ticket by ID. */
    public function get(string $ticketId): array
    {
        return $this->http->get("/tickets/{$ticketId}");
    }

    /** Update a ticket's status or priority. */
    public function update(
        string  $ticketId,
        ?string $status               = null,
        ?string $priority             = null,
        bool    $notifyCustomer       = false,
        ?string $notificationMessage  = null
    ): array {
        $body = ['notifyCustomer' => $notifyCustomer];
        if ($status)              $body['status']              = $status;
        if ($priority)            $body['priority']            = $priority;
        if ($notificationMessage) $body['notificationMessage'] = $notificationMessage;
        return $this->http->patch("/tickets/{$ticketId}", $body);
    }

    /** Resolve a ticket and notify the customer. */
    public function resolve(string $ticketId, ?string $message = null): array
    {
        return $this->update($ticketId, 'resolved', notifyCustomer: true, notificationMessage: $message);
    }
}
