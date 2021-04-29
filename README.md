# Apitin - ultra lightweight API framework

* Requires `PHP 8.0+`
* Development can be done with `built-in web server`
* Can be used with `modules` and pure `callbacks`
* Supports magical loading with `static dependency injection`

## TODO

* Support for Apache
* Route caching

## Getting started

Create new project using `composer` with:

```
composer create-project apitin/apitin example-api
```

See `app/ExampleModule.php`:
```php
<?php

use Apitin\Database;
use Apitin\Module;
use Apitin\Router;

class ExampleModule extends Module
{
    /**
     * Build routes for module
     */
    public function route(Router $router): void
    {
        // Route / to: $this->test(...)
        $router->get('/', function() {
            return $this->call('test');
        });
    }

    /**
     * Callback handler for a route
     * 
     * Database implements DI class
     * -> $db will be automatically populated with Database::factory()
     */
    public function test(Database $db)
    {
        return ['foo' => 'bar'];
    }
}
```