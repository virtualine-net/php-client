# Virtualine PHP Client

[![PHP Version](https://img.shields.io/badge/PHP-%3E%3D7.4-blue.svg)](https://php.net)
[![License](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE)
[![Packagist](https://img.shields.io/packagist/v/virtualine-net/php-client.svg)](https://packagist.org/packages/virtualine-net/php-client)

<div align="center">
  <img src="https://virtualine.net/assets/img/LightLogo.gif" alt="Virtualine Logo" width="200"/>
</div>

PHP client library for interacting with the Virtualine API. This library provides a simple and efficient way to manage virtual servers and services through the Virtualine platform.

## Features

- Simple API authentication using API key and email
- Credit balance management
- Product listing and management
- Virtual server operations:
  - Create new services
  - Start/Stop/Reboot servers
  - Change passwords
  - Terminate services
  - Suspend/Unsuspend services
  - Reinstall templates
  - Get WMSK URLs
- Service information and details retrieval
- Connection testing

## Requirements

- PHP 7.4 or higher
- Composer
- Virtualine HTTP Client

## Installation

You can install the library via Composer:

```bash
composer require virtualine-net/php-client
```

## Usage

### Basic Setup

```php
use Virtualine\VirtualineClient;

// Initialize the client with your API credentials
$client = new VirtualineClient('your-api-key', 'your-email');

// Test the connection
if ($client->testConnection()) {
    echo "Successfully connected to Virtualine API";
}
```

### Creating a New Service

```php
// Create a new service with detailed configuration
$result = $client->createService($productId, [
    'cycle' => "monthly",
    'hostname' => "service.virtualine.net",
    'username' => "root",
    'password' => "password",
    'nsprefix[]' => 'ns1',
    'nsprefix[]' => 'ns2',
    'configurations[Operating System]' => 1
]);

if ($result) {
    echo "Service created successfully!";
} else {
    echo "Failed to create service.";
}
```

### Managing Services

```php
// Get available products
$products = $client->getProducts();

// Get service details
$serviceDetails = $client->getServiceDetails('service-id');

// Manage service state
$client->start('service-id');
$client->stop('service-id');
$client->reboot('service-id');

// Change service password
$client->changePassword('service-id', 'new-password');

// Reinstall service
$client->reinstall('service-id', 'template-id', 'new-password');
```

### Billing and Credits

```php
// Check credit balance
$creditBalance = $client->getCredit();
```

## API Methods

### Authentication
- `__construct(string $apiKey, string $email)` - Initialize the client with API credentials

### Connection
- `testConnection(): bool` - Test the API connection

### Billing
- `getCredit(): float` - Get current credit balance

### Products
- `getProducts(): array` - Get list of available products

### Services
- `getServiceDetails(string $serviceId): array` - Get detailed information about a service
- `getInfo(string $serviceId): array` - Get service information
- `createService(string $productId, array $params): array|false` - Create a new service
- `start(string $serviceId): array|false` - Start a service
- `stop(string $serviceId): array|false` - Stop a service
- `reboot(string $serviceId): array|false` - Reboot a service
- `changePassword(string $serviceId, string $password): array|false` - Change service password
- `terminate(string $serviceId): array|false` - Terminate a service
- `suspend(string $serviceId): array|false` - Suspend a service
- `unsuspend(string $serviceId): array|false` - Unsuspend a service
- `reinstallTemplates(string $serviceId): array` - Get available reinstall templates
- `reinstall(string $serviceId, string $templateId, string $password): array|false` - Reinstall a service
- `getWMKSUrl(string $serviceId): string|false` - Get WMSK URL for a service

## Error Handling

The client throws exceptions in case of errors:

- `InvalidArgumentException` - When required parameters are missing
- `RuntimeException` - When API requests fail

## Support

For support, please contact Virtualine support team or create an issue in the repository.

## License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## Authors

- Virtualine.net Team
