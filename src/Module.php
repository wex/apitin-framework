<?php declare(strict_types = 1);

namespace Apitin;

use Apitin\Router\NotFoundException;
use ReflectionMethod;

abstract class Module
{
    protected Application $application;

    public function __construct(Application &$application)
    {
        $this->application = $application;
        
        $this->route($application->router);

        $this->onRegister($this->application);
    }

    public function onRegister(Application &$application): void
    {

    }

    public function call(string $methodName, array $arguments = [])
    {
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

    abstract public function route(Router $router): void;
}