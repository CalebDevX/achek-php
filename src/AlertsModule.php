<?php

namespace AchekConnect;

class AlertsModule
{
    public function __construct(private HttpClient $http) {}

    /**
     * Send a custom WhatsApp alert to any phone number.
     *
     * @param string $phoneNumber E.164 format
     * @param string $message     Plain text or WhatsApp markdown (*bold*, _italic_)
     * @param string $category    "alert" | "transaction" | "notification"
     */
    public function send(
        string  $phoneNumber,
        string  $message,
        string  $category        = 'alert',
        ?string $ticketId        = null,
        ?string $transactionRef  = null
    ): array {
        $body = [
            'phoneNumber' => $phoneNumber,
            'message'     => $message,
            'category'    => $category,
        ];
        if ($ticketId)       $body['ticketId']       = $ticketId;
        if ($transactionRef) $body['transactionRef'] = $transactionRef;
        return $this->http->post('/alerts/send', $body);
    }

    /**
     * Send a formatted transaction alert via WhatsApp.
     *
     * @param string      $phoneNumber
     * @param string      $type         "credit"|"debit"|"transfer"|"reversal"
     * @param float       $amount
     * @param string      $reference    Transaction reference / ID
     * @param string      $currency     e.g. "NGN"
     * @param string|null $accountName
     * @param float|null  $balance      Balance after transaction
     * @param string|null $description  Narration
     *
     * @example
     *   $client->alerts->transaction(
     *       '+2348XXXXXXXXX', 'debit', 15000, 'TXN-001',
     *       accountName: 'Emeka Okafor', balance: 240000
     *   );
     */
    public function transaction(
        string  $phoneNumber,
        string  $type,
        float   $amount,
        string  $reference,
        string  $currency    = 'NGN',
        ?string $accountName = null,
        ?float  $balance     = null,
        ?string $description = null
    ): array {
        $symbol = $currency === 'NGN' ? '₦' : $currency . ' ';
        $labels = [
            'credit'   => '💰 Credit Alert',
            'debit'    => '💸 Debit Alert',
            'transfer' => '🔄 Transfer Alert',
            'reversal' => '↩️ Reversal Alert',
        ];
        $label = $labels[$type] ?? ucfirst($type) . ' Alert';
        $fmt   = fn(float $n) => number_format($n, 2);

        $lines = ["*{$label}*", ''];
        $lines[] = "Amount: *{$symbol}{$fmt($amount)}*";
        if ($accountName) $lines[] = "Account: {$accountName}";
        $lines[] = "Ref: `{$reference}`";
        if ($description) $lines[] = "Narration: {$description}";
        if ($balance !== null) $lines[] = "Balance: *{$symbol}{$fmt($balance)}*";
        $lines[] = '';
        $lines[] = '_Powered by Achek Connect_';

        return $this->send($phoneNumber, implode("\n", $lines), 'transaction', transactionRef: $reference);
    }
}
