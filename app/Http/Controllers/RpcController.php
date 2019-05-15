<?php
/**
 * Created by PhpStorm.
 * User: dewdrops
 * Date: 2019-02-02
 * Time: 10:58
 */

namespace App\Http\Controllers;


use App\Exceptions\AuthException;
use App\Services\AuthService;
use App\Support\RpcParams;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Laravel\Lumen\Routing\Controller;

class RpcController extends Controller
{

    public function loginByWechat(AuthService $service, RpcParams $params)
    {
        return $service->loginByWechat(
            $params['code'],
            $params->get('iv'),
            $params->get('encrypted')
        );
    }

    public function loginByPassword(AuthService $service, RpcParams $params)
    {
        return $service->loginByPassword($params['username'], $params['password']);
    }

    public function loginByToken(AuthService $service, RpcParams $params)
    {
        return $service->loginByToken($params['token']);
    }

    public function loginAsGuest(AuthService $service)
    {
        return $service->loginAsGuest();
    }

    public function register(AuthService $service, RpcParams $params)
    {
        return $service->register($params['username'], $params['password']);
    }

    public function bindWechat(AuthService $service, RpcParams $params)
    {
        return $service->bindWechat(
            $params['id'], $params['code'], $params->get("allowRefresh", false)
        );
    }

    public function bindPassword(AuthService $service, RpcParams $params)
    {
        return $service->bindPassword(
            $params['id'], $params['username'], $params['password'], $params->get("allowRefresh", false)
        );
    }

    public function checkToken(AuthService $service, RpcParams $params)
    {
        return $service->checkToken($params['token']);
    }

    public function dispatchRpc(Request $request)
    {
        $endpoint = $request->input('method');
        $id = $request->input('id');
        $jsonrpc = $request->input('jsonrpc');
        $params = $request->input('params');
        try {
            $result = $this->execute($endpoint, $params);
            $wrapped = [
                'result' => $result ?? (object) null,
            ];
        }
        catch (\Exception $exception) {
            Log::error("{$exception->getMessage()}, stack: {$exception->getTraceAsString()}");
            $wrapped = [
                'error' => [
                    'code' => $exception->getCode() ?: AuthException::CODE_AUTH_FAILED,
                    'message' => $exception->getMessage(),
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
        if ($endpoint === '@batch') {
            return $this->executeBatch($params);
        }
        else {
            return $this->executeSingle($endpoint, $params);
        }
    }

    private function executeBatch(array $params)
    {
        $calls = $params['calls'];
        return DB::transaction(function () use ($calls) {
            return array_map(function ($call) {
                return $this->executeSingle($call['method'], $call['params']);
            }, $calls);
        });
    }

    private function executeSingle($endpoint, $inputParams)
    {
        $class = new \ReflectionClass(static::class);
        $methodName = camel_case($endpoint);
        $method = $class->getMethod($methodName);

        $args = [];
        foreach ($method->getParameters() as $parameter) {
            $paramClass = $parameter->getClass();
            if ($paramClass) {
                if ($paramClass->name === RpcParams::class) {
                    $args[] = new RpcParams($inputParams);
                }
                elseif ($paramClass->name === User::class) {
                    $args[] = Auth::user();
                }
                else {
                    $args[] = app($paramClass->name);
                }
            }
        }

        return $method->invokeArgs($this, $args);
    }


}