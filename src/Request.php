<?php declare(strict_types = 1);

namespace Apitin;

use Apitin\DI;

class Request implements DI
{
    public static function factory(): self
    {
        return new static;
    }

    public static function post(?string $key = null, $default = null)
    {
        if (is_null($key)) return $_POST;

        return array_key_exists($key, $_POST) ?
            $_POST[$key] :
            $default;
    }

    public static function get(?string $key = null, $default = null)
    {
        if (is_null($key)) return $_GET;

        return array_key_exists($key, $_GET) ?
            $_GET[$key] :
            $default;
    }

    public static function files(string $key): array
    {
        $files = [];

        if (array_key_exists($key, $_FILES)) {

            if (is_array($_FILES[$key]['name'])) {

                foreach ($_FILES[$key]['name'] as $fileIndex => $t) {
                    $files[] = [
                        'name'      => $_FILES[$key]['name'][$fileIndex],
                        'type'      => $_FILES[$key]['type'][$fileIndex],
                        'tmp_name'  => $_FILES[$key]['tmp_name'][$fileIndex],
                        'error'     => $_FILES[$key]['error'][$fileIndex],
                        'size'      => $_FILES[$key]['size'][$fileIndex],
                    ];
                }

            } else {

                $files[] = [
                    'name'      => $_FILES[$key]['name'],
                    'type'      => $_FILES[$key]['type'],
                    'tmp_name'  => $_FILES[$key]['tmp_name'],
                    'error'     => $_FILES[$key]['error'],
                    'size'      => $_FILES[$key]['size'],
                ];

            }

        }

        return $files;
    }
}