<?php


namespace App\Support;


use App\Exceptions\AuthException;
use GuzzleHttp\Client;
use Illuminate\Support\Arr;

class UesRpcClient
{

    private $endpoint;
    private $http;

    function __construct(string $endpoint)
    {
        $this->http = new Client([
            'timeout' => 2.0
        ]);
        $this->endpoint = $endpoint;
    }

    function call(string $method, array $params = null)
    {
        $resp = $this->http->post($this->endpoint, [
            'json' => [
                'method' => $method,
                'params' => $params
            ]
        ]);
        $parsed = json_decode($resp->getBody()->getContents(), true);
        if (Arr::has($parsed, 'error')) {
            $error = $parsed['error'];
            throw new AuthException($error['message'], $error['code']);
        }
        else {
            return $parsed['result'];
        }
    }

    function batch(array $specs, bool $transactional = false) {
        return $this->call(
            $transactional ? '@transactional' : '@batch',
            [
                'calls' => array_map(function ($spec, $key) {
                    return [
                        'method' => $key,
                        'params' => $spec
                    ];
                }, $specs)
            ]
        );
    }

}
