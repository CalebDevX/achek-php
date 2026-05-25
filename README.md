# achek/achek — PHP SDK

Official PHP SDK for [Achek](https://achek.com.ng) — WhatsApp OTP, automated alerts, transaction notifications, broadcasts, support ticket tracking, transactional email, and webhook utilities for Nigeria and Africa.

[![Packagist](https://img.shields.io/packagist/v/achek/achek)](https://packagist.org/packages/achek/achek)
[![License: MIT](https://img.shields.io/badge/License-MIT-green.svg)](LICENSE)

## Install

```bash
composer require achek/achek
```

## Quick Start

```php
<?php
require 'vendor/autoload.php';

use AchekConnect\AchekConnect;

$client = new AchekConnect('your_api_key');

// Send WhatsApp OTP
$result    = $client->otp->send('+2348012345678');
$requestId = $result['requestId'];

// Verify the code the user entered
$verification = $client->otp->verify($requestId, $userCode);
if ($verification['valid']) {
    loginUser(); // ✅
}
```

> Get your API key from the [Achek dashboard](https://achek.com.ng/dashboard/api-keys).

---

## OTP Verification

```php
// Simple send
$result = $client->otp->send('+2348012345678');

// With custom template (Growth+ plans) + idempotency key
$result = $client->otp->send(
    '+2348012345678',
    template:       'Hi {{name}}, your {{company}} code is {{code}}. Valid 10 mins.',
    recipientName:  'Emeka',
    companyName:    'MyApp',
    idempotencyKey: 'otp-req-user-789', // safe to retry with the same key
);

// Verify
$result = $client->otp->verify($requestId, '847293');
// ['valid' => true, 'message' => 'OTP verified successfully']

// Fetch OTP logs
$logs = $client->otp->logs(limit: 20, status: 'verified');
```

---

## Transaction Alerts

Send formatted debit/credit/transfer alerts directly to a customer's WhatsApp:

```php
$client->alerts->transaction(
    '+2348012345678',
    type:        'debit',
    amount:      15000,
    reference:   'TXN-20240519-001',
    accountName: 'Emeka Okafor',
    balance:     240000,
    description: 'Transfer to Kuda',
);
```

This automatically sends a clean WhatsApp message:
```
💸 Debit Alert

Amount: ₦15,000.00
Account: Emeka Okafor
Ref: `TXN-20240519-001`
Narration: Transfer to Kuda
Balance: ₦240,000.00

_Powered by Achek_
```

### Custom Alerts

```php
$client->alerts->send(
    '+2348012345678',
    '*Your loan has been approved!* 🎉 Check your account.',
    category: 'notification',
);
```

---

## Broadcasts

Send a single WhatsApp message to up to 1,000 recipients at once:

```php
$result = $client->broadcasts->send(
    'Black Friday Promo',
    "🔥 *50% OFF* today only!\nCode: *FRIDAY50*",
    ['+2348012345678', '+2349087654321'], // up to 1000
);

// Check delivery status
$status = $client->broadcasts->status($result['id']);
```

---

## Support Ticket Tracking

Create support tickets and keep customers updated on WhatsApp automatically:

```php
// Create — customer gets a WhatsApp notification
$ticket   = $client->tickets->create(
    '+2348012345678',
    'Payment not reflecting',
    description:         'Paid ₦5,000 but order not updated',
    priority:            'high',
    notifyCustomer:      true,
);
$ticketId = $ticket['ticketId'];

// Update — customer gets a WhatsApp update
$client->tickets->update(
    $ticketId,
    status:              'in_progress',
    notifyCustomer:      true,
    notificationMessage: "We're on it! Expected resolution: 2 hours.",
);

// Resolve
$client->tickets->resolve($ticketId, 'Your issue has been fixed!');

// List open tickets
$openTickets = $client->tickets->list(status: 'open');
```

---

## Transactional Email

Send transactional emails via Achek's configured SMTP. Sender address and domain are set in your dashboard.

```php
// HTML email
$client->email->send(
    to:      'customer@example.com',
    subject: 'Your OTP code',
    html:    '<p>Your code is <strong>847293</strong>. Valid for 10 minutes.</p>',
);

// Plain-text email
$client->email->send(
    to:       'customer@example.com',
    subject:  'Order confirmed',
    text:     'Thanks for your order! Delivery in 3–5 business days.',
    fromName: 'MyShop Support',
);
```

---

## AI Chatbot & Webhook Events

Achek includes a built-in AI chatbot that responds to WhatsApp messages automatically. Configure it from the dashboard — no extra API calls needed.

### Webhook events

Set a webhook URL in **AI Bot → Webhook URL** in your dashboard. Achek will POST to it on these events:

| Event | Fired when | Key fields |
|---|---|---|
| `message.incoming` | Customer sends a message to your bot | `phone`, `message`, `timestamp` |
| `message.outgoing` | Bot replies to the customer | `phone`, `message`, `timestamp` |
| `spam.quarantine` | Sender exceeded velocity limit | `phone`, `quarantined_until`, `message_count` |
| `handoff.requested` | Consecutive depth reached / human took over | `phone`, `reason`, `exchanges`, `bot_config_id` |
| `ticket.created` | Bot opens a support ticket | `ticket_id`, `phone`, `subject`, `priority` |
| `lead.captured` | Bot saves a customer lead | `ticket_id`, `phone`, `name`, `email` |

### Verifying webhook signatures

Achek signs every delivery with HMAC-SHA256. Always verify before trusting the payload:

```php
<?php
require 'vendor/autoload.php';

use AchekConnect\AchekWebhookHelper;

$helper = new AchekWebhookHelper($_ENV['ACHEK_WEBHOOK_SECRET']);

$sig  = $_SERVER['HTTP_X_ACHEK_SIGNATURE'] ?? '';
$body = file_get_contents('php://input');

if (!$helper->verify($sig, $body)) {
    http_response_code(400);
    exit('Invalid webhook signature');
}

$event = $helper->parse($body);

switch ($event['event']) {
    case 'handoff.requested':
        // Notify your support team
        break;
    case 'lead.captured':
        $phone = $event['phone'] ?? '';
        $name  = $event['name']  ?? '';
        // → sync to your CRM, spreadsheet, etc.
        break;
    case 'spam.quarantine':
        // Log for monitoring
        break;
}

http_response_code(200);
```

### Querying captured leads

```php
$allTickets = $client->tickets->list(status: 'open');
$leads = array_filter($allTickets, fn($t) => ($t['metadata']['type'] ?? '') === 'lead');

foreach ($leads as $lead) {
    echo $lead['ticketId'];    // "LEAD-1748000000000-XY12"
    echo $lead['phoneNumber']; // "+2348012345678"
    print_r($lead['metadata']); // ['source' => 'whatsapp_bot', 'type' => 'lead', ...]
}
```

---

## Error Handling

```php
use AchekConnect\AchekConnectException;

try {
    $client->otp->send('+2348012345678');
} catch (AchekConnectException $e) {
    echo $e->getMessage();    // "No active subscription"
    echo $e->getStatusCode(); // 402
    echo $e->getErrorCode();  // "SUBSCRIPTION_REQUIRED"
}
```

---

## Configuration

```php
$client = new AchekConnect(
    apiKey:         'your_api_key',
    baseUrl:        'https://api.achek.com.ng', // override if self-hosted
    timeout:        15,                          // seconds (default: 15)
    maxAttempts:    3,                           // total tries incl. first (default: 3)
    initialDelayMs: 500,                         // first retry: 500 ms, then 1 000, 2 000…
);
```

---

## Requirements

- PHP >= 8.1
- `ext-curl`, `ext-json`, `ext-hash`

---

## API Reference

| Module | Method | Description |
|---|---|---|
| `otp` | `send($phone, ...)` | Send WhatsApp OTP |
| `otp` | `verify($requestId, $code)` | Verify OTP code |
| `otp` | `logs(...)` | Fetch OTP delivery logs |
| `alerts` | `send($phone, $message, ...)` | Send custom WhatsApp alert |
| `alerts` | `transaction($phone, ...)` | Send formatted transaction alert |
| `broadcasts` | `send($name, $message, $recipients)` | Send broadcast to up to 1,000 numbers |
| `broadcasts` | `list()` | List recent broadcasts |
| `broadcasts` | `status($id)` | Get broadcast delivery status |
| `tickets` | `create($phone, $subject, ...)` | Create support ticket |
| `tickets` | `list(...)` | List tickets |
| `tickets` | `get($ticketId)` | Get ticket by ID |
| `tickets` | `update($ticketId, ...)` | Update status/priority |
| `tickets` | `resolve($ticketId, $message?)` | Resolve and notify customer |
| `email` | `send($to, $subject, ...)` | Send transactional email |
| `AchekWebhookHelper` | `verify($sig, $body)` | Verify HMAC-SHA256 signature |
| `AchekWebhookHelper` | `parse($body)` | Parse raw webhook payload |

---

## Links

- Website: [achek.com.ng](https://achek.com.ng)
- Dashboard: [achek.com.ng/dashboard](https://achek.com.ng/dashboard)
- Docs: [achek.com.ng/docs](https://achek.com.ng/docs)
- Issues: [github.com/CalebDevX/achek-php/issues](https://github.com/CalebDevX/achek-php/issues)

## License

MIT — see [LICENSE](LICENSE)
