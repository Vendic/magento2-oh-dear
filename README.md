# Oh Dear module for Magento 2
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
        ]
    ]
```

## Checks
TODO

## Write your own checks
TODO
