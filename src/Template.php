<?php declare(strict_types = 1);

namespace Apitin;

class Template implements Renderable
{

    protected string    $filename;
    protected array     $data       = [];
    protected           $scope      = null;
    protected bool      $buffered   = false;

    public static function create(string $filename, array $data = [], &$scope = null, bool $buffered = false)
    {
        $instance = new static;

        $instance->filename = $filename;
        $instance->data     = $data;
        $instance->scope    = $scope;
        $instance->buffered = $buffered;

        return $instance;
    }

    public function render()
    {
        $renderer = function() {
            extract(func_get_arg(1));

            if (func_get_arg(2)) ob_start();
            
            require func_get_arg(0);

            if (!func_get_arg(2)) return;
            
            $html = ob_get_contents();
            ob_end_clean();

            return $html;
        };

        return $renderer->call(
            is_null($this->scope) ? $this : $this->scope,
            $this->filename,
            $this->data,
            $this->buffered
        );
    }

    public function partial(string $filename, array $data = [], $scope = null, bool $buffered = false)
    {
        $renderer = function() {
            extract(func_get_arg(1));

            if (func_get_arg(2)) ob_start();
            
            require func_get_arg(0);

            if (!func_get_arg(2)) return;
            
            $html = ob_get_contents();
            ob_end_clean();

            return $html;
        };

        $baseDir = dirname($this->filename);

        return $renderer->call(
            is_null($scope) ? $this : $scope,
            "{$baseDir}/{$filename}",
            $this->data + $data,
            $buffered
        );
    }
}