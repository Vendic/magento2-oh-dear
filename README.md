# Oh Dear module for Magento 2

### Configuration
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
