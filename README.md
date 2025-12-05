# LaraMailer PHP SDK

A PHP SDK for interacting with the LaraMailer API.

## Installation

You can install the package via composer:

```bash
composer require laramailer/sdk
```

## Usage

Initialize the client with your API token and the base URL of your LaraMailer instance.

```php
use LaraMailer\Sdk\Client;

$client = new Client('YOUR_API_TOKEN', 'https://your-laramailer-instance.com/api/v1');
```

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

### Sending Mail

```php
// Send an email
$response = $client->mail()->send($accountId, [
    'to' => ['recipient@example.com'],
    'subject' => 'Hello World',
    'html_body' => '<h1>Hello!</h1><p>This is a test email.</p>',
    'text_body' => 'Hello! This is a test email.', // Optional
    'attachments' => [ // Optional
        [
            'path' => 'path/to/file.pdf', 
            'name' => 'document.pdf',
            'content_type' => 'application/pdf'
        ]
    ]
]);

// List email tasks
$tasks = $client->mail()->listTasks();

// Get specific task
$task = $client->mail()->getTask($taskId);
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
