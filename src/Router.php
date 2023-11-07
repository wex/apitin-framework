<?php declare(strict_types = 1);

namespace Apitin;

use Closure;
use Apitin\Router\InvalidMethodException;
use Apitin\Router\NotFoundException;
use ReflectionFunction;

class Router
{
    private $scope = null;

    private $prefix = null;

    private $routeMap = [
        'GET'       => [],
        'HEAD'      => [],
        'POST'      => [],
        'PUT'       => [],
        'DELETE'    => [],
        'CONNECT'   => [],
        'OPTIONS'   => [],
        'TRACE'     => [],
        'PATCH'     => [],
    ];

    private $nameMap = [];

    public function __construct($scope = null)
    {
        $this->scope = $scope;
    }

    public function find(string $name)
    {
        return isset($this->nameMap[$name]) ?
            ltrim($this->nameMap[$name], '/') :
            null;
    }

    public function on(string $method, string $route, Closure $callback, string $name = null): self
    {
        $_method    = strtoupper($method);
        $_route     = static::route2regex(trim($route, '/'), $this->prefix);
        $_callback  = $this->scope ? Closure::bind($callback, $this->scope) : $callback;
        
        if (!array_key_exists($_method, $this->routeMap)) {
            throw new InvalidMethodException("Unknown HTTP Method: {$_method}");
        }

        $this->routeMap[ $_method ][ $_route ] = $_callback;

        if ($name) {
            $this->nameMap[ $name ] = $route;
        }

        return $this;
    }

    public function match(string $method, string $uri)
    {
        $_method    = strtoupper($method);
        $_uri       = trim($uri, '/');

        if (!array_key_exists($_method, $this->routeMap)) {
            throw new InvalidMethodException("Unknown HTTP Method: {$_method}");
        }

        foreach ($this->routeMap[$_method] as $_regex => $_callback) {

            if (preg_match($_regex, $_uri, $params)) {

                $_params = array_filter(
                    $params,
                    function($v, $k) {
                        return !is_numeric($k);
                    },
                    ARRAY_FILTER_USE_BOTH
                );

                $refledCallback = new ReflectionFunction($_callback);

                foreach ($refledCallback->getParameters() as $reflectedParameter) {
                    if (array_key_exists($reflectedParameter->getName(), $_params)) continue;
                    if ($reflectedParameter->allowsNull()) $_params[ $reflectedParameter->getName() ] = null;
                    if (!$reflectedParameter->hasType()) continue;

                    $reflectedType  = $reflectedParameter->getType();
                    $className      = $reflectedType->getName();

                    if (class_exists($className) && is_subclass_of($className, DI::class)) {
                        $_params[ $reflectedParameter->getName() ] = $className::factory();
                    }
                }
                
                return $_callback(...$_params);

            }

        }

        throw new NotFoundException("No match: {$_method} {$_uri}");
    }

    public function with(string $prefix, Closure $callback): self
    {
        $this->prefix = $prefix;

        $callback();

        $this->prefix = null;

        return $this;
    }

    public function get(string $route, Closure $callback): self
    {
        return $this->on('GET', $route, $callback);
    }

    public function head(string $route, Closure $callback): self
    {
        return $this->on('HEAD', $route, $callback);
    }

    public function post(string $route, Closure $callback): self
    {
        return $this->on('POST', $route, $callback);
    }

    public function put(string $route, Closure $callback): self
    {
        return $this->on('PUT', $route, $callback);
    }

    public function delete(string $route, Closure $callback): self
    {
        return $this->on('DELETE', $route, $callback);
    }

    public function connect(string $route, Closure $callback): self
    {
        return $this->on('CONNECT', $route, $callback);
    }

    public function options(string $route, Closure $callback): self
    {
        return $this->on('OPTIONS', $route, $callback);
    }

    public function trace(string $route, Closure $callback): self
    {
        return $this->on('TRACE', $route, $callback);
    }

    public function patch(string $route, Closure $callback): self
    {
        return $this->on('PATCH', $route, $callback);
    }

    public function any(string $route, Closure $callback): self
    {
        foreach ($this->routeMap as $httpMethod => $t) {
            $this->on($httpMethod, $route, $callback);
        }

        return $this;
    }

    public static function route2regex(string $route, ?string $prefix = null): string
    {
        $_route = $prefix ? trim("{$prefix}/{$route}", '/') : $route;

        $regex = preg_replace('/\{([^\/}]+)\}/i', '(?P<\1>[^/]+)', $_route);
        $regex = str_replace('*', '(?P<uri>.*?)', $regex);

        return "|^{$regex}$|i";
    }
}