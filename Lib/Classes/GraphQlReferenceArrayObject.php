<?php
// @author: C.A.D. BONDJE DOUE
// @file: GraphQlReferenceArrayObject.php
// @date: 20231014 17:06:21
namespace igk\io\GraphQl;

use ArrayAccess;
use IGK\System\Polyfill\ArrayAccessSelfTrait;
use JsonSerializable;

///<summary></summary>
/**
* 
* @package igk\io\GraphQl
*/
class GraphQlReferenceArrayObject implements ArrayAccess, JsonSerializable{
    use ArrayAccessSelfTrait;
    private $m_tab;

    public function jsonSerialize(): mixed {
        return $this->m_tab;
    }
    public function __debugInfo()
    {
        return $this->to_array();
    }
    /**
     * copy array 
     * @return mixed 
     */
    public function to_array(){
        return $this->m_tab;
    }

    protected function _access_offsetSet($k, & $v){
        $this->m_tab[$k] = $v;
    }

    protected function _access_offsetGet($k){
        return igk_getv($this->m_tab, $k);
    }
    protected function _access_OffsetUnset($k){
        unset($this->m_tab[$k]);
    }
    public function replaceWith($tab, $key){
        $outdata = & $this->m_tab;
   
        $pos = $pos ?? array_search($key, array_keys($outdata));
        $t3 = array_slice($outdata, 0, $pos+1, true ) + $tab +  array_slice($outdata, $pos, count($outdata)-$pos, true );
        unset($t3[$key]);
        $outdata = $t3;
    }
}