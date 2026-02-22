# KRA GavaConnect PHP SDK (Unofficial)

> Unofficial PHP SDK for Kenya Revenue Authority (KRA) GavaConnect APIs

[![PHP](https://img.shields.io/packagist/php-v/kra-php/sdk)](https://packagist.org/packages/mitigoa/kra-php-sdk)
[![License](https://img.shields.io/packagist/l/kra-php/sdk)](https://packagist.org/packages/mitigoa/kra-php-sdk)
[![Version](https://img.shields.io/packagist/v/kra-php/sdk)](https://packagist.org/packages/mitigoa/kra-php-sdk)

## Overview

The KRA GavaConnect PHP SDK is a comprehensive, PSR-compliant PHP library for interacting with the Kenya Revenue Authority's GavaConnect APIs. It provides a clean, type-safe interface for:

- PIN Validation
- Tax Compliance Certificate (TCC) Verification
- Taxpayer Obligations & Liabilities
- NIL Return Filing
- eTIMS Invoice Submission
- e-Slip Verification
- Excise License Checks

## Features

- **Framework-agnostic core** - Zero Laravel/Symfony dependencies
- **PSR compliance** - PSR-4 autoloading, PSR-7 HTTP messages, PSR-18 HTTP client, PSR-6 caching
- **Auto token management** - OAuth2 client credentials with silent refresh
- **Typed responses** - Every API returns strongly-typed DTOs
- **Granular exceptions** - `AuthException`, `ApiException`, `RateLimitException`
- **Offline testable** - Full sandbox mode + Mockery-ready interfaces
- **Plugin ecosystem** - Laravel, WordPress, WooCommerce, Symfony, Filament

## Requirements

- PHP 8.1+
- Composer

## Installation

```bash
composer require kra-php/sdk
```

## Quick Start

```php
use KraPHP\KraClient;

$kra = new KraClient([
    'client_id'     => getenv('KRA_CLIENT_ID'),
    'client_secret' => getenv('KRA_CLIENT_SECRET'),
    'environment'   => 'sandbox', // or 'production'
]);

// Validate a PIN
$pin = $kra->pin()->validate('A000000010');
echo $pin->taxpayerName;    // "ACME KENYA LIMITED"
echo $pin->pinStatus;       // "ACTIVE"

// Validate TCC
$tcc = $kra->tcc()->validate('A000000010', 'TCC-2024-XXXXX');
echo $tcc->isValid;         // true
echo $tcc->expiryDate;     // "2024-12-31"

// Get tax obligations
$obligations = $kra->taxpayer()->getObligations('A000000010');

// File NIL return
$result = $kra->returns()->fileNil([
    'pin'         => 'A000000010',
    'obligation'  => 'VAT',
    'period'      => '2024-10',
    'reason'      => 'No taxable supplies',
]);

// Submit eTIMS invoice
$invoice = new EtimsInvoice([
    'invoiceNumber' => 'INV-2024-001',
    'buyerPin'      => 'A000000020',
    'buyerName'     => 'BUYER COMPANY LTD',
    'invoiceDate'   => '2024-11-01',
    'currency'      => 'KES',
    'items'         => [
        [
            'description' => 'Web Development Services',
            'quantity'    => 1,
            'unitPrice'   => 50000.00,
            'vatRate'     => 16,
            'vatAmount'   => 8000.00,
        ],
    ],
    'totalExclVat'  => 50000.00,
    'totalVat'      => 8000.00,
    'totalInclVat'  => 58000.00,
]);

$response = $kra->etims()->submitInvoice($invoice);
echo $response->invoiceId;
echo $response->controlUnit;
echo $response->qrCode;
```

## Configuration

```php
$config = [
    // Required
    'client_id'     => 'your-client-id',
    'client_secret' => 'your-client-secret',

    // Environment
    'environment'   => 'sandbox', // 'sandbox' | 'production'

    // Base URLs (optional - defaults provided)
    'sandbox_base_url' => 'https://api-sandbox.developer.go.ke',
    'prod_base_url'    => 'https://api.developer.go.ke',
    'etims_sandbox_url' => 'https://etims-api-sbx.kra.go.ke',
    'etims_prod_url'   => 'https://etims-api.kra.go.ke/etims-api',

    // Cache (optional)
    'cache_driver'  => 'file', // 'file' | 'redis' | 'memcached' | 'array'
    'cache_ttl'     => 3300,  // seconds

    // HTTP (optional)
    'timeout'       => 30,    // seconds
    'retry_attempts' => 3,

    // eTIMS mTLS (optional)
    'etims_cert_path' => '/path/to/client.crt',
    'etims_key_path'  => '/path/to/client.key',
];

$kra = new KraClient($config);
```

## Environment Variables

```env
# OAuth2 Credentials
KRA_CLIENT_ID=your-client-id
KRA_CLIENT_SECRET=your-client-secret

# Environment
KRA_ENVIRONMENT=sandbox

# Cache
KRA_CACHE_DRIVER=redis
KRA_CACHE_TTL=3300

# HTTP
KRA_TIMEOUT=30
KRA_RETRY_ATTEMPTS=3

# eTIMS
KRA_ETIMS_CERT_PATH=/path/to/client.crt
KRA_ETIMS_KEY_PATH=/path/to/client.key
```

## Error Handling

```php
use KraPHP\Exceptions\AuthException;
use KraPHP\Exceptions\ApiException;
use KraPHP\Exceptions\RateLimitException;

try {
    $pin = $kra->pin()->validate('A000000010');
} catch (AuthException $e) {
    // Authentication failed
    echo $e->getMessage();
    echo $e->getKraErrorCode(); // e.g., 'INVALID_CREDENTIALS'
} catch (ApiException $e) {
    // API error response
    echo $e->getHttpStatusCode(); // e.g., 404
    echo $e->getKraErrorCode();   // e.g., 'PIN_NOT_FOUND'
} catch (RateLimitException $e) {
    // Rate limit exceeded
    echo $e->getRetryAfter(); // seconds until retry
}
```

## Testing

```bash
# Install dependencies
composer install

# Run unit tests
vendor/bin/phpunit --testsuite unit

# Run with coverage
vendor/bin/phpunit --coverage-html coverage/

# Run static analysis
vendor/bin/phpstan analyse

# Fix code style
vendor/bin/php-cs-fixer fix
```

## API Coverage

| API | Endpoint | Method |
|-----|----------|--------|
| PIN Validator | `/pin/validate` | GET |
| PIN by ID | `/pin/check-by-id` | GET |
| TCC Checker | `/tcc/validate` | GET |
| Taxpayer Obligations | `/taxpayer/obligations` | GET |
| Taxpayer Liabilities | `/taxpayer/liabilities` | GET |
| NIL Return | `/returns/nil` | POST |
| e-Slip Verify | `/eslip/verify` | GET |
| Excise License | `/excise/check` | GET |
| eTIMS Invoice | `/etims/invoice` | POST |
| eTIMS Stock IO | `/etims/stock` | POST |
| eTIMS Purchase | `/etims/purchase` | POST |

## Documentation

- [KRA Developer Portal](https://developer.go.ke)
- [API Documentation](https://developer.go.ke/docs)

## License

MIT License - see [LICENSE](LICENSE) file.

## Support

- Email: apisupport@kra.go.ke
- GitHub Issues: https://github.com/kra-php/sdk/issues

---

Built for Kenya. Open to the world.
