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
 
    private $m_data;
    private $m_schema;

    /**
     * get the schema definition 
     * @return SchemaDefinition 
     */
    public function getSchemaDefinition()
    {
        return $this->m_schema; 
    }
    /**
     * set query type name 
     * @param string $n 
     * @return void 
     */
    public function setQueryTypeName(string $n){
         $this->m_schema->queryType->name = $n;
    }
    /**
     * set mutation type name
     * @param null|string $n 
     * @return $this 
     */
    public function setMutationTypeName(?string $n){
        if (is_null($n)){
            $this->m_schema->mutationType = null;
            return $this;
        }
        $c = $this->m_schema->mutationType = $this->m_schema->mutationType ?? new SchemaQueryTypeDefinition();
        $c->name = $n; 
        return $this;
   }
    public function __construct()
    {
        $this->m_schema =  new SchemaDefinition();
        $this->m_data = (object)['data'=>[
            '__schema'=>$this->m_schema
        ]];
    }
    public function render(){
        $empty = JSonEncodeOption::IgnoreEmpty();
        return JSon::Encode($this->m_data, $empty,  JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }
    /**
     * add object type definition 
     * @param mixed $r 
     * @return void 
     */
    public function addType(SchemaTypeDefinition $r){
        $this->m_schema->types[] = $r;
    }
}

