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
        if (php_sapi_name() === 'cli') {

            $_method    = 'GET';
            $_uri       = $_SERVER['argv'][1];

        } else {
            
            $_method    = $method ?: $_SERVER['REQUEST_METHOD'];

            if ($uri !== null) {
                $_uri = $uri;
            } else if (array_key_exists('__uri', $_REQUEST)) {
                $_uri = $_REQUEST['__uri'];
            } else {
                $_uri = $_SERVER['REQUEST_URI'];
            }

            if (strpos($_uri, '?') !== false) {
                $_uri = substr($_uri, 0, strpos($_uri, '?'));
            }

        }

        return $this->router->match($_method, $_uri);
    }
}