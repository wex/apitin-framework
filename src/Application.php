<?php declare(strict_types = 1);

namespace Apitin;

use Apitin\Router\ServeWithBuiltinException;
use RuntimeException;

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
        if (isCli()) {

            if (!array_key_exists(1, $_SERVER['argv'])) 
                throw new RuntimeException("Missing command - type 'php index.php commandHere'");

            $_method    = 'GET';
            $_uri       = $_SERVER['argv'][1];

        } else {

            if (isBuiltin()) {
                $requestFile = $_SERVER['DOCUMENT_ROOT'] . $_SERVER['REQUEST_URI'];
                if (strpos($requestFile, '?') !== false) {
                    $requestFile = substr($requestFile, 0, strpos($requestFile, '?'));
                }
                if (file_exists($requestFile) && !is_dir($requestFile)) {
                    throw new ServeWithBuiltinException();
                }
            }
            
            $_method    = $method ?: $_SERVER['REQUEST_METHOD'];

            if ($uri !== null) {
                $_uri = $uri;
            } else {
                $_uri = isBuiltin() ? $_REQUEST['REQUEST_URI'] : ($_REQUEST['__uri'] ?? '');
            }

            if (strpos($_uri, '?') !== false) {
                $_uri = substr($_uri, 0, strpos($_uri, '?'));
            }

        }

        return $this->router->match($_method, $_uri);
    }
}