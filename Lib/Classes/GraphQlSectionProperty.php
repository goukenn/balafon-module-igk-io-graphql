<?php
// @author: C.A.D. BONDJE DOUE
// @file: GraphQlProperty.php
// @date: 20231015 15:15:19
namespace igk\io\GraphQl;

use IGK\Database\Mapping\Traits\ModelMappingDataTrait;
use IGK\Models\ModelBase;

///<summary></summary>
/**
* 
* @package igk\io\GraphQl
*/
class GraphQlSectionProperty extends GraphQlDocProperty{
    use ModelMappingDataTrait;
     /**
     * store loaded properties
     * @var array
     */
    var $properties = [];

    protected $_is_indexing = false;

   

    /**
     * get data 
     * @param mixed $source source data 
     * @param null|callable $mapping callable for mapping 
     * @return ?array of binding filtered array of resource resource. 
     * @throws IGKException 
     */
    public function getData($source, ?callable $mapping=null){ 
        $data = $this->_getData($source, $mapping); 
        if ($data){
            if ($data instanceof GraphQlReferenceArrayObject){
                $data = $data->to_array();
            }
            $data = array_filter($data, function($d){
                return !empty($d); 
            });
        }
        return $data;
    }
    protected function _getData($source, ?callable $mapping=null){
        if ($source instanceof IGraphQlIndexArray)
        {    
            $rtab = $source->to_array();
            return $this->_getIndexedData($rtab, $mapping); 
        }
        if (is_array($source) && !igk_array_is_assoc($source)){
            return $this->_getIndexedData($source, $mapping); 
        }
        // $refdata = $this->_getIndexedData([$source], $mapping); //& $this->_getFieldData($source, $mapping);
        $refdata = $this->_getFieldData($source, $mapping);
   
        return $refdata;
    }

    private function _getIndexedData(array $data, ?callable $mapping=null){
        $tab = [];
        $_HOOK_KEY = GraphQlHooks::HookName(GraphQlHooks::HOOK_END_ENTRY);
        $this->_is_indexing = true;
        while($this->_is_indexing && (count($data)>0)){
            $q = array_shift($data);
            if (!$q){
                continue;
            }
            // $o =& $this->_getFieldData($q, $mapping);  
            $o = $this->_getFieldData($q, $mapping);  
           
            igk_hook($_HOOK_KEY, [$this, $q, & $o]);
            $tab[] = & $o;
            unset($o);
            $o = null;
        }  
        if (!$this->_is_indexing){
          if(count($tab)==1){
            $tab = array_shift($tab);
          }  
        }
        return $tab;
    }

      /**
     * 
     * @param mixed $source 
     * @param null|callable $mapping 
     * @return array 
     * @throws IGKException 
     * @throws ArgumentTypeNotValidException 
     * @throws ReflectionException 
     */
    protected  function _getFieldData($source, ?callable $mapping=null){
        $o = new GraphQlReferenceArrayObject; 
        if ($source instanceof ModelBase){
            // get mapping source 
            $source = $this->getModelMappingData($source, $mapping); 
        } else if ($source instanceof IGraphQlMappingData){
            $source = $source->getMappingData($mapping);
        } 
        $v_core_data = $source;//  $path && $source ? igk_conf_get($source, $path) : $source;
        foreach($this->properties as $k=>$def){ 
            $data = $v_core_data;  
            $key = $def->getKey() ?? $k; 
            $o[$key] = igk_getv($v_core_data, $def->name, $def->default);  
        }
        return $o;
    }
}