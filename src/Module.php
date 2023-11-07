<?php declare(strict_types = 1);

namespace Apitin;

use Apitin\Module\Inject;
use Apitin\Router\NotFoundException;
use ReflectionClass;
use ReflectionFunctionAbstract;
use ReflectionMethod;
use ReflectionObject;
use ReflectionProperty;
use RuntimeException;

abstract class Module
{
    protected Application $application;

    public function __construct(Application &$application)
    {
        $this->application = $application;
        
        $this->routeWithAttributes($application->router);
        $this->route($application->router);

        $this->onRegister($this->application);
    }

    public function findRoute(string $name)
    {
        return $this->application->router->find($name);
    }

    public function findUrl(string $name, array $parameters = [])
    {
        $url = $this->application->router->find($name);

        if (!$url) return;

        if (preg_match_all('|\{([^\}]+)\}|', $url, $matches)) {

            foreach ($matches[1] as $match) {
                $url = str_replace(
                    sprintf('{%s}', $match),
                    (string) $parameters[$match] ?? '',
                    $url
                );
            }

        }

        return $url;
    }

    public function onRegister(Application &$application): void
    {

    }

    public function onCall(Application &$application): void
    {
        
    }

    public function populateDI(string $methodName, array $arguments = []): array
    {
        $method = new ReflectionMethod($this, $methodName);

        foreach ($method->getParameters() as $t) {
            if (array_key_exists($t->getName(), $arguments)) continue;
            if ($t->allowsNull()) $arguments[ $t->getName() ] = null;
            if ($t->isDefaultValueAvailable()) $arguments[ $t->getName() ] = $t->getDefaultValue();
            if (!$t->hasType()) continue;

            $reflectedType  = $t->getType();
            $className      = $reflectedType->getName();
            if (class_exists($className) && is_subclass_of($className, DI::class)) {
                $argumentName = $t->getName();
                $arguments[ $argumentName ] = $className::factory();
            }
        }

        return $arguments;
    }

    public function populateInject(array $arguments = []): array
    {
        $reflectedClass = new ReflectionClass($this);

        foreach ($reflectedClass->getProperties() as $property) {
            foreach ($property->getAttributes(Inject::class) as $inject) {
                $propertyName       = $property->getName();
                $injectParameters   = $inject->getArguments();
                $injectWith         = $injectParameters[0];
                
                if (!is_subclass_of($injectWith, DI::class, true)) throw new RuntimeException(sprintf(
                    'Inject is only possible with DI-classes.'
                ));

                $arguments[ $propertyName ] = $injectWith::factory();
            }
        }

        return $arguments;
    }

    public function call(string $methodName, array $arguments = [])
    {
        if (!method_exists($this, $methodName)) throw new NotFoundException("Handler not found: {$methodName}");
        
        $arguments = $this->populateDI($methodName, $arguments);
        $arguments = $this->populateInject($arguments);

        $this->onCall($this->application);

        return $this->$methodName(...$arguments);
    }

    protected function routeWithAttributes(Router $router): void
    {
        $reflectionClass = new ReflectionObject($this);

        foreach ($reflectionClass->getMethods(ReflectionMethod::IS_PUBLIC) as $reflectionMethod) {
            $methodName = $reflectionMethod->getName();
            foreach ($reflectionMethod->getAttributes(Route::class) as $routeAttribute) {
                $routeParameters = $routeAttribute->getArguments();

                foreach ( is_string($routeParameters[1]) ? [$routeParameters[1]] : $routeParameters[1] as $httpMethod) {
                    $router->on($httpMethod, $routeParameters[0], function(...$__params) use ($methodName) {
                        unset( $__params['__params'] );
                        return $this->call($methodName, $__params);
                    }, $routeParameters[2] ?? null);
                }
            }
        }
    }

    public function route(Router $router): void
    {
        
    }
}