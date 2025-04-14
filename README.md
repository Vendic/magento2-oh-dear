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
