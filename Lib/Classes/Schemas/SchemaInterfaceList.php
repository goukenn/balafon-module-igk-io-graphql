<?php
// @author: C.A.D. BONDJE DOUE
// @file: SchemaInterfaceList.php
// @date: 20231006 23:28:00
namespace igk\io\GraphQl\Schemas;

use ArrayAccess;
use IGK\Helper\IJSonEncodeArrayDefinition;
use IGK\System\Polyfill\ArrayAccessSelfTrait;
use JsonSerializable;

///<summary></summary>
/**
* 
* @package igk\io\GraphQl\Schemas
*/
class SchemaInterfaceList implements JsonSerializable, ArrayAccess, IJSonEncodeArrayDefinition{
    use ArrayAccessSelfTrait;
    private $m_data;

    function _access_OffsetGet($i){
        return igk_getv($this->m_data, $i);
    }
    function _access_OffsetSet($i, $value){
        if ($i){
            $this->m_data[$i] = $value;
        }
        else 
            $this->m_data[] = $value;
    }
    function _access_offsetExists($i){
        return isset($this->m_data[$i]);
    }
    public function isEmpty(): bool { 
        return empty($this->m_data);
    }

    public function isRequired(): bool {
        return true;
    }
 
    public function jsonSerialize(): mixed { 
        if (empty($this->m_data)){
            return [];
        }
        return $this->m_data;
    } 
}