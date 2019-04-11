<?php
/**
 * Created by PhpStorm.
 * User: dewdrops
 * Date: 2019-02-04
 * Time: 22:07
 */

namespace App\Support;


use ArrayAccess;
use ArrayIterator;
use Illuminate\Support\Arr;
use IteratorAggregate;

class RpcParams implements ArrayAccess, IteratorAggregate
{

    private $params;

    public function __construct(array $params)
    {
        $this->params = $params;
    }

    public function getJson(): array
    {
        return $this->params;
    }

    public function get(string $path, $default = null)
    {
        return Arr::get($this->params, $path, $default);
    }

    public function offsetExists($offset)
    {
        return isset($this->params);
    }

    public function offsetGet($offset)
    {
        return $this->params[$offset];
    }

    public function offsetSet($offset, $value)
    {
        if (is_null($offset)) {
            $this->params[] = $value;
        }
        else {
            $this->params[$offset] = $value;
        }
    }

    public function offsetUnset($offset)
    {
        unset($this->params[$offset]);
    }

    public function getIterator()
    {
        return new ArrayIterator($this->params);
    }
}