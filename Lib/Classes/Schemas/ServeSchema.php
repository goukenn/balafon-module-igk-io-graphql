<?php
// @author: C.A.D. BONDJE DOUE
// @file: ServeSchema.php
// @date: 20231006 23:29:15
namespace igk\io\GraphQl\Schemas;

use IGK\Helper\JSon;
use IGK\Helper\JSonEncodeOption;

///<summary></summary>
/**
* 
* @package igk\io\GraphQl\Schemas
*/
class ServeSchema{
 
    var $m_data;
    private $schema;

    public function __construct()
    {
        $this->schema =  new SchemaDefinition();
        $this->m_data = (object)['data'=>[
            '__schema'=>$this->schema
        ]];
    }
    public function render(){
        $empty = JSonEncodeOption::IgnoreEmpty();
        return JSon::Encode($this->m_data, $empty,  JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }
    public function addType($r){
        $this->schema->types[] = $r;
    }
}

