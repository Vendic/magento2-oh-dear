# Oh Dear module for Magento 2 [![Integration Test](https://github.com/Vendic/magento2-oh-dear/actions/workflows/integration.yml/badge.svg)](https://github.com/Vendic/magento2-oh-dear/actions/workflows/integration.yml)
This module adds [Application health monitoring](https://ohdear.app/features/application-health-monitoring) using [Oh Dear](https://ohdear.app/) to Magento 2. It allows you to easily write your own custom checks. Additionally, it ships with a number of checks out of the box. 

## Installation
```bash
composer require vendic/magento2-oh-dear-checks
```

## Configuration
Some checks have an optional configuration. You can configure these checks in the `env.php`. Example:
```php
    'ohdear' => [
        \Vendic\OhDear\Checks\Diskspace::class => [
            'max_percentage_used' => '86'
        ],
        \Vendic\OhDear\Checks\CpuLoad::class => [
            'max_load_last_minute' => 10,
            'max_load_last_five_minutes' => 8,
            'max_load_last_fifteen_minutes' => 6
        ],
        \Vendic\OhDear\Checks\DatabaseConnectionCount::class => [
            'failed_treshold' => 100,
            'warning_treshold' => 80
        ],
        \Vendic\OhDear\Checks\PhpFpmCount::class => [
            'failed_treshold' => 100,
            'warning_treshold' => 80
        ],
    ]
```

## Disabling Checks
To disable any check, add an entry to your `env.php` with the check class name and set `enabled` to `false`:

```php
    'ohdear' => [
        'Vendic\\OhDear\\Checks\\CpuLoad' => [
            'enabled' => false
        ],
        'Vendic\\OhDear\\Checks\\Diskspace' => [
            'enabled' => false
        ],
        'Vendic\\OhDear\\Checks\\TwoFactorAuthentication' => [
             'enabled' => false
        ]
    ],
```

## Checks
TODO

### Store fronts
Oh Dear monitors one domain per site, but a single Magento instance often serves many store views on
different domains. The `store_fronts` check reports on the availability of all those child store domains:

- An hourly cron (`vendic_ohdear_check_store_fronts`) collects the base URLs of all active store views
  (deduplicated by domain, excluding the default store view's domain since Oh Dear already monitors it)
  and requests them in parallel with a 10 second timeout, following up to 5 redirects.
- A store front counts as reachable only when the request ends in an HTTP 200.
- Only failing domains are stored and reported. When one or more domains are down the check fails and the
  failed domains (with their HTTP status or connection error) are attached as meta under `failed_domains`.
- The check warns when the cron has never run or when the cached results are older than 2 hours, so a
  broken cron does not go unnoticed.

Disable it like any other check via `env.php`:
```php
    'ohdear' => [
        \Vendic\OhDear\Checks\StoreFronts::class => [
            'enabled' => false
        ],
    ]
```
Disabling the check also stops the cron from making any requests.

## Write your own checks
1. Create a new class that implements `Vendic\OhDear\Interfaces\CheckInterface`, place it in 'Checks'. This class will contain the main logic of your check.
2. Add your new class to the 'checks' argument of `Vendic\OhDear\Api\CheckListInterface`
```xml
    <type name="Vendic\OhDear\Api\CheckListInterface">
        <arguments>
            <argument name="checks" xsi:type="array">
                ...
                <item name="your_new_check" xsi:type="object">Vendic\OhDear\Checks\YourNewCheck</item>
                ...
            </argument>
        </arguments>
    </type>
```
3. Preferabbly add a test for your check. See `Vendic\OhDear\Test\Integration\Checks\` for examples.
4. Test your output on: https://magento2.test/oh-dear-health-application-check-results. Your GET request should include the header `oh-dear-health-check-secret`. The header value should match the Magento config value of `ohdear/health_check/secret`. If you don't have this header, you will get a 'No health secret provided' response. 
5. Open a PR with your new check!
