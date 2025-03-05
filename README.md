# DevSecTools SDK for PHP

**Experimental. Untested. Not ready for primetime. Exploring code generation.**

## Overview

A PHP SDK for interacting with the [DevSecTools API].

This client provides an easy way to interact with the [DevSecTools API], which scans websites for security-related information such as HTTP version support and TLS configurations.

* Uses [Guzzle] to handle HTTP requests and supports both synchronous and asynchronous (parallel) requests.
* Fully-typed, with type-hints for JetBrains IDEs, VS Code, and other tools which leverage those patterns.
* Built to leverage features in PHP 8.1.
* _Zero backwards-compatibility guarantees_ until we hit v1.0.

Will only support [versions of PHP which receive support](https://www.php.net/supported-versions.php) from the core team. Presently, this means PHP 8.1+.

## Model

* [openapi.json](https://github.com/northwood-labs/devsec-tools/raw/refs/heads/main/openapi.json)

## Usage

### Instantiating with default configuration

```php
<?php
declare(strict_types=1);

require 'vendor/autoload.php';

use DevSecTools\Client;
use DevSecTools\Endpoint;

// Using default configuration
$api = new Client();
```

### Custom configuration

<details>
<summary>See example…</summary>

```php
<?php
declare(strict_types=1);

require 'vendor/autoload.php';

use DevSecTools\Client;
use DevSecTools\Endpoint;

// Using custom configuration
$api = new Client([
  'base_uri'        => Endpoint::LOCALDEV,
  'timeout_seconds' => 10,
]);
```

</details>

### Updating configuration at runtime

<details>
<summary>See example…</summary>

```php
<?php
declare(strict_types=1);

require 'vendor/autoload.php';

use DevSecTools\Client;
use DevSecTools\Endpoint;

// Using default configuration
$api = new Client();

// Updating the configuration
$api->setBaseUri(Endpoint::LOCALDEV);
$api->setTimeoutSeconds(15);
```

</details>

### Making single requests

<details>
<summary>See example…</summary>

```php
<?php
declare(strict_types=1);

require 'vendor/autoload.php';

use DevSecTools\Client;
use DevSecTools\Endpoint;

// Using default configuration
$api = new Client();

$httpInfo = $api->http('example.com');
print_r($httpInfo);
```

</details>

### Making parallel/batch requests

<details>
<summary>See example…</summary>

```php
<?php
declare(strict_types=1);

require 'vendor/autoload.php';

use DevSecTools\Client;
use DevSecTools\Endpoint;

// Using default configuration
$api = new Client();

// Running multiple requests in parallel
$batchResults = $api->batch([
    ['method' => 'http', 'url' => 'apple.com'],
    ['method' => 'tls',  'url' => 'google.com'],
]);

print_r($batchResults);
```

</details>

[DevSecTools API]: https://devsec.tools
[Guzzle]: https://docs.guzzlephp.org
