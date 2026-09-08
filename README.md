# LaraMailer for Laravel

Laravel mail transport + PHP SDK for a LaraMailer instance.

## Installation

Requires PHP 8.2+ and Laravel 11, 12 or 13.

Until the package is on Packagist, add the repository to your app's `composer.json`:

```json
{
    "repositories": [
        { "type": "vcs", "url": "git@github.com:LaraMailer/sdk_php.git" }
    ]
}
```

```bash
composer require laramailer/laravel:@dev
```

## Configuration

`config/mail.php`:

```php
'mailers' => [
    'laramailer' => ['transport' => 'laramailer'],
],
```

`.env`:

```env
MAIL_MAILER=laramailer
LARAMAILER_ENDPOINT=https://mail.example.com
LARAMAILER_TOKEN=your_access_token
LARAMAILER_ACCOUNT_ID=1
LARAMAILER_TRACKING_ENABLED=true
```

`LARAMAILER_ACCOUNT_ID` may be the numeric account id or the UUID shown in the dashboard.

## Sending through Laravel Mail

```php
Mail::to($supplier->email)->send(
    (new QuotationRequestMail($quotation))
        ->metadata('contract_id', $contract->id)
        ->metadata('procedure_id', $procedure->id)
        ->metadata('user_id', auth()->id())
        ->tag('quotation')
);
```

- `metadata()` values are stored on the LaraMailer task and are filterable.
- `tag()` values are joined into `metadata.tags`.
- Custom header `X-Idempotency-Key` becomes the `Idempotency-Key` request header (safe retries).
- Custom header `X-Tracking-Enabled: false` disables open/click tracking for that email.
- `Mail::send()` returns a `SentMessage`; `getMessageId()` is the LaraMailer task id.
- Embedded/inline images (`embed()`) are not forwarded; use absolute image URLs in HTML.
- When the mailable sets no From address, LaraMailer uses the account's own address.

## Reading history and proof

```php
use LaraMailer\Sdk\Facades\LaraMailer;

$tasks = LaraMailer::mail()->listTasks(['metadata' => ['contract_id' => 42], 'status' => 'completed']);
$task = LaraMailer::mail()->getTask($taskId);
// $task['data']['sent_at'], ['smtp_response'], ['opened_at'], ['delivered_at'], ['tracking_events'], ['eml_url']

$eml = LaraMailer::mail()->downloadEml($taskId); // raw RFC 822 message as sent
```

## Explicit send

```php
LaraMailer::mail()->send($accountId, [
    'to' => [['email' => 'supplier@example.com', 'name' => 'Supplier']],
    'subject' => 'Quotation request',
    'html_body' => '<p>...</p>',
    'text_body' => '...',
    'metadata' => ['contract_id' => 42],
], idempotencyKey: 'quotation-42-supplier-9');
```

## Sending with a template

Templates are authored and published in the LaraMailer dashboard (per team). Send data, not HTML:

```php
LaraMailer::mail()->sendTemplate(
    accountId: 1,
    template: 'quotation-request',
    variables: ['ref' => 'CP/2026/17', 'deadline' => '2026-09-20', 'items' => [['name' => 'Paper', 'qty' => 10]]],
    message: ['to' => [['email' => 'supplier@example.com']], 'metadata' => ['contract_id' => 42]],
    idempotencyKey: 'quotation-42-supplier-9',
);

LaraMailer::templates()->list();
LaraMailer::templates()->preview(1, ['ref' => 'CP/2026/17']);
```

Templates use Mustache syntax (`{{ref}}`, `{{#items}}…{{/items}}`, helpers `{{#date}}`, `{{#money}}`, `{{#upper}}`). Variables are validated against the template's schema; the task records the exact template version used. The Laravel mail transport is not involved — it always carries fully rendered mail.

### Accounts

```php
// List all accounts
$accounts = $client->accounts()->list();

// Get a specific account
$account = $client->accounts()->get($accountId);

// Create a new account
$newAccount = $client->accounts()->create([
    'name' => 'My Account',
    'email' => 'me@example.com',
    // ... other fields
]);

// Update an account
$client->accounts()->update($accountId, [
    'name' => 'Updated Name',
]);

// Delete an account
$client->accounts()->delete($accountId);
```

### Attachments

Upload an attachment to be used in emails.

```php
$attachment = $client->attachments()->upload('/path/to/image.png');
// Returns ['path' => '...', 'url' => '...']
```

### OAuth2

```php
// List OAuth2 configs
$configs = $client->oauth2()->list();

// Initiate OAuth2 flow
$url = $client->oauth2()->initiate($configId);
```

## Testing

```bash
composer install
composer test
```

CI runs the suite on Orchestra Testbench across a matrix of Laravel 11, 12 and 13 (PHP 8.3 and 8.4) — see `.github/workflows/tests.yml`.
