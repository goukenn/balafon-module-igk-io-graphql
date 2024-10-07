<?php

namespace igk\io\GraphQl;

use ArrayAccess;
use IGK\System\IArrayKeyExists;
use IGK\System\Polyfill\ArrayAccessSelfTrait;

class RefArrayData implements ArrayAccess, IArrayKeyExists,IGraphQlIndexArray {
    use ArrayAccessSelfTrait;
    private $m_args;

    public function __construct(array & $args)
    {
        $this->m_args = & $args;
    }

    public function to_array(): array {
        return $this->m_args;
    }

    public function keyExists(string $name): bool { 
        return key_exists($name, $this->m_args);
    }
    protected function _access_offsetGet($k){
        return $this->m_args[$k];
    }
    protected function _access_offsetSet($k, $v){
        $this->m_args[$k] = $v;
    }
    public function __isset($name)
    {
        return false;// isset($this->m_args[$name]);
    }
    public function __debugInfo()
    {
        return [];
    }

}