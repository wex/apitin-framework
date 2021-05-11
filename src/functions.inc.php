<?php

namespace Apitin {
    
    if (!defined('APP_PATH')) define('APP_PATH', '');

    function urlTo($uri)
    {
        return sprintf(
            "%s://%s%s/%s%s%s",
            ($_SERVER['SERVER_PORT'] === '443') ? 'https' : 'http',
            $_SERVER['SERVER_NAME'],
            !in_array($_SERVER['SERVER_PORT'], array('80', '443')) ? sprintf(":%d", $_SERVER['SERVER_PORT']) : '',
            trim(substr($_SERVER['SCRIPT_NAME'], 0, -9), '/'),
            strlen(trim(substr($_SERVER['SCRIPT_NAME'], 0, -9), '/')) ? '/' : '',
            trim($uri, '/')
        );
    }

    function config(string $key, $default = null)
    {
        static $config = null;

        if ($config === null) {
            $config = [];

            if (file_exists(APP_PATH . '.env')) {
                $config = parse_ini_file(APP_PATH . '.env');
            }
        }

        return array_key_exists($key, $config) ?
            $config[$key] :
            $default;
    }

    function dprintf(...$params)
    {
        if (!isDebugging()) return;

        $text = sprintf(
            "\e[1;32m%s\e[0m \e[0;36m%s\e[0m\n",
            strtoupper($_SERVER['REQUEST_METHOD']),
            $_SERVER['REQUEST_URI']
        );

        $text .= sprintf(...$params);

        error_log($text);
    }

    function log_r(...$values)
    {
        if (!config('LOGFILE', false)) return;
        
        $callStack = debug_backtrace();

        $sourceFile = $callStack[0]['file'];
        $sourceLine = $callStack[0]['line'];

        $text = sprintf(
            "{$sourceFile}:{$sourceLine}"
        );

        foreach ($values as $value) {

            $text .= "\n";

            switch (gettype($value)) {
                case 'object':
                    $text .= sprintf("%s ", get_class($value));

                    if ($value instanceof \Throwable) {
                        $text .= sprintf(
                            "%s:%s\n%s\n%s\n",
                            $value->getFile(),
                            $value->getLine(),
                            $value->getMessage(),
                            $value->getTraceAsString()
                        );
                    }
                    
                    $value = get_object_vars($value);

                case 'array':
                    $text .= json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_SLASHES);
                    break;
            
                case 'double':
                    $value = sprintf("%.6f", $value);
                case 'integer':
                case 'string':
                    $text .= $value;
                    break;

                default:
                    $text .= sprintf(
                        "\e[0;31m%s\e[0m",
                        gettype($value)
                    );
                
            }
        }

        file_put_contents(
            config('LOGFILE', false), 
            sprintf(
                "[%s] %s\n",
                date('Y-m-d H:i:s'),
                $text
            ),
            FILE_APPEND
        );
    }

    function isDebugging(): bool
    {
        if (config('DEBUG', false)) return true;
        if (php_sapi_name() === 'cli-server') return true;
        
        return false;
    }

    function dprint_r(...$values)
    {
        if (!isDebugging()) return;

        $callStack = debug_backtrace();

        $sourceFile = $callStack[0]['file'];
        $sourceLine = $callStack[0]['line'];

        $text = sprintf(
            "\e[1;32m%s\e[0m",
            "{$sourceFile}:{$sourceLine}"
        );

        foreach ($values as $value) {

            $text .= "\n";

            switch (gettype($value)) {
                case 'object':
                    $text .= sprintf("%s ", get_class($value));

                    if ($value instanceof \Throwable) {
                        $text .= sprintf(
                            "%s:%s\n%s\n%s\n",
                            $value->getFile(),
                            $value->getLine(),
                            $value->getMessage(),
                            $value->getTraceAsString()
                        );
                    }

                    $value = get_object_vars($value);
                case 'array':
                    $text .= json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_SLASHES);
                    break;
            
                case 'double':
                    $value = sprintf("%.6f", $value);
                case 'integer':
                case 'string':
                    $text .= $value;
                    break;

                default:
                    $text .= sprintf(
                        "\e[0;31m%s\e[0m",
                        gettype($value)
                    );
                
            }
        }

        error_log($text);
    }

}