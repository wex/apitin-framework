<?php declare(strict_types = 1);

namespace Apitin;

use Apitin\Http\HttpException;
use RuntimeException;

class Http
{
    const DEFAULTS = [
        'user_agent'    => 'ApitinHttp/1.0',
        'timeout'       => 3.0,
    ];

    protected $streamMeta;
    protected $streamData;

    protected function request($url, $streamContext): self
    {
        $stream = fopen(
            $url,
            'r',
            false,
            $streamContext
        );

        if ($stream === false) throw new RuntimeException("POST {$url} failed.");

        $this->streamMeta = stream_get_meta_data($stream);
        $this->streamData = stream_get_contents($stream);

        if ($this->streamMeta['timed_out']) throw new RuntimeException("POST {$url} timed out.");

        list($httpVersion, $httpCode, $httpMessage) = explode(" ", $this->streamMeta['wrapper_data'][0], 3);

        if ($httpCode < 200) throw new HttpException($httpMessage, intval($httpCode));
        if ($httpCode >= 300) throw new HttpException($httpMessage, intval($httpCode));

        fclose($stream);

        return $this;
    }

    public function getHeaders()
    {
        $headers = [];

        foreach ($this->streamMeta['wrapper_data'] as $headerIndex => $headerString) {
            if (!$headerIndex) continue;
            list($headerKey, $headerValue) = explode(':', $headerString, 2);
            $headers[ trim($headerKey) ] = trim($headerValue);
        }

        return $headers;
    }

    public function __toString()
    {
        return $this->body();
    }

    public function body()
    {
        return $this->streamData;
    }

    public function json()
    {
        return json_decode(
            $this->streamData,
            true
        );
    }

    public static function post(string $url, array $payload = [], array $headers = []): self
    {
        $streamContext = stream_context_create([
            'http' => static::DEFAULTS + [
                'method'        => 'POST',
                'ignore_errors' => true,
                'header'        => [
                    'Content-Type: application/x-www-form-urlencoded',
                    ...$headers,
                ],
                'content'       => http_build_query($payload),
            ],
        ]);

        $instance = new static;

        return $instance->request(
            $url,
            $streamContext
        );
    }

    public static function postJson(string $url, array $payload = [], array $headers = []): self
    {
        $streamContext = stream_context_create([
            'http' => static::DEFAULTS + [
                'method'        => 'POST',
                'ignore_errors' => true,
                'header'        => [
                    'Content-Type: application/json',
                    ...$headers,
                ],
                'content'       => json_encode($payload),
            ],
        ]);

        $instance = new static;

        return $instance->request(
            $url,
            $streamContext
        );
    }

    public static function get(string $url, array $query = [], array $headers = []): self
    {
        $requestUrl = (strpos($url, '?') === false) ?
            "{$url}?" : "{$url}&";
        $requestUrl .= http_build_query($query);

        $streamContext = stream_context_create([
            'http' => static::DEFAULTS + [
                'method'        => 'GET',
                'ignore_errors' => true,
                'header'        => [
                    ...$headers,
                ],
            ],
        ]);

        $instance = new static;
        
        return $instance->request(
            $requestUrl,
            $streamContext
        );
    }
}