<?php declare(strict_types = 1);

namespace Apitin;

use Apitin\Router\NotFoundException;
use ReflectionMethod;
use ReflectionObject;

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

    public function onRegister(Application &$application): void
    {

    }

    public function onCall(Application &$application): void
    {
        
    }

    public function call(string $methodName, array $arguments = [])
    {
        $this->onCall($this->application);

        if (!method_exists($this, $methodName)) throw new NotFoundException("Handler not found: {$methodName}");
        
        $reflectedMethod = new ReflectionMethod($this, $methodName);

        foreach ($reflectedMethod->getParameters() as $t) {
            if (array_key_exists($t->getName(), $arguments)) continue;
            if ($t->allowsNull()) $arguments[ $t->getName() ] = null;
            if ($t->isDefaultValueAvailable()) $arguments[ $t->getName() ] = $t->getDefaultValue();
            if (!$t->hasType()) continue;

            $reflectedType  = $t->getType();
            $className      = $reflectedType->getName();
            if (class_exists($className) && is_subclass_of($className, DI::class)) {
                $arguments[ $t->getName() ] = $className::factory();
            }
        }

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
                    });
                }
            }
        }
    }

    abstract public function route(Router $router): void;
}