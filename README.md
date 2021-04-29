# Apitin - ultra lightweight API framework

* Requires `PHP 8.0+`
* Development can be done with `built-in web server`
* Apache & mod_rewrite supported
* Can be used with `modules` and pure `callbacks`
* Supports magical loading with `static dependency injection`

## TODO

* Route caching
* Create documentation

## Getting started

1. Create new project using `composer` with:

```
composer create-project apitin/apitin example-api
```

2. Start internal web server
```
cd example-api
php -S 127.0.0.1:3000 -t .
```

3. Visit `http://127.0.0.1:3000/` and start developing!