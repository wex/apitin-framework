<?php declare(strict_types = 1);

namespace Apitin;

class Application
{
    public Router $router;

    private array $modules;

    public function __construct()
    {
        $this->router   = new Router;
        $this->modules  = [];
    }

    public function register($module, ...$modules): self
    {
        $this->modules[$module] = new $module($this);

        foreach ($modules as $t) {
            $this->modules[$t] = new $t($this);
        }

        return $this;
    }

    public function __invoke($method = null, $uri = null)
    {
        $_method    = $method ?: $_SERVER['REQUEST_METHOD'];
        $_uri       = $uri ?: ((strpos($_SERVER['REQUEST_URI'], '?') !== false) ? 
                                    substr($_SERVER['REQUEST_URI'], 0, strpos($_SERVER['REQUEST_URI'], '?')) : 
                                    $_SERVER['REQUEST_URI']
                              );

        return $this->router->match($_method, $_uri);
    }
}