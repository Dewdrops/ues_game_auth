<?php


namespace App\Traits;


use App\Support\RpcParams;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

trait UesRpcDispatcher
{
    public function dispatchRpc(Request $request)
    {
        $endpoint = $request->input('method');
        $id = $request->input('id');
        $jsonrpc = $request->input('jsonrpc');
        $params = $request->input('params');
        try {
            $result = $this->execute($endpoint, $params);
            $wrapped = [
                'result' => $result,
            ];
        }
        catch (\Throwable $throwable) {
            Log::error("{$throwable->getMessage()}, stack: {$throwable->getTraceAsString()}");
            $wrapped = [
                'error' => [
                    'code' => $throwable->getCode() ?: -1,
                    'message' => $throwable->getMessage(),
                ]
            ];
        }
        if (!is_null($id)) {
            $wrapped['id'] = $id;
        }
        if (!is_null($jsonrpc)) {
            $wrapped['jsonrpc'] = $jsonrpc;
        }
        return $wrapped;
    }

    private function execute(string $endpoint, array $params)
    {
        if ($endpoint === '@transactional') {
            return DB::transaction(function () use ($params) {
                return $this->executeBatch($params);
            });
        }
        if ($endpoint === '@batch') {
            return $this->executeBatch($params);
        }
        else {
            return $this->executeSingle($endpoint, $params);
        }
    }

    private function executeBatch(array $params)
    {
        return array_map(
            function ($call) {
                return $this->executeSingle($call['method'], $call['params']);
            },
            $params['calls']
        );
    }

    private function executeSingle($endpoint, $inputParams)
    {
        $class = new \ReflectionClass(static::class);
        $methodName = Str::camel($endpoint);
        $method = $class->getMethod($methodName);

        $args = [];
        foreach ($method->getParameters() as $parameter) {
            $paramClass = $parameter->getClass();
            if ($paramClass) {
                if ($paramClass->name === RpcParams::class) {
                    $args[] = new RpcParams($inputParams);
                }
                else {
                    $args[] = app($paramClass->name);
                }
            }
        }

        return $method->invokeArgs($this, $args);
    }
}
