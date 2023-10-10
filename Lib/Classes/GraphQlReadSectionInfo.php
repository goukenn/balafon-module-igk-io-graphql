<?php
// @author: C.A.D. BONDJE DOUE
// @file: GraphQlReadSectionInfo.php
// @date: 20231009 17:41:29
namespace igk\io\GraphQl;

use IGK\Models\ModelBase;
use IGK\System\Models\ModelBase as ModelsModelBase;
use IGKException;
use IGKSysUtil as sysutil;

///<summary></summary>
/**
* information used to read brank section 
* @package igk\io\GraphQl
*/
class GraphQlReadSectionInfo extends GraphQlDocProperty{
    /**
     * name of this section 
     * @var mixed
     */
    var $name;

    /**
     * field alias 
     * @var mixed
     */
    var $alias;
    /**
     * section type - query or method
     * @var mixed
     */
    var $type;

    /**
     * store loaded properties
     * @var array
     */
    var $properties = [];
    /**
     * parent field section
     * @var ?static
     */
    var $parent;

    /**
     * get stored object data
     * @var mixed
     */
    var $data;


    /**
     * throw exception on reading properties
     * @var mixed
     */
    var $throwException = true;
    /**
     * mo
     * @param mixed $data 
     * @param mixed $key 
     * @param null|callable $mapping model mapping data
     * @return mixed 
     * @throws IGKException 
     */
    public function getValue($data, $key, ?callable $mapping){
         
        $f_info = igk_getv($this->properties, $key);
        $v_def = $f_info ? $f_info->default: null;  
        if ($data instanceof IGraphQlMappingData){
           return $data->getMappingValue($key, $v_def);
        }
        else if ($data instanceof ModelBase){
            $info = $data->getTableInfo();
            $tn = $info ? sysutil::GetModelTypeNameFromInfo($info): null;
          
            if ($mapping && $tn){ 
                $map_data = $mapping($tn); 
                if ($map_data){
                    $b = $data->map($map_data); 
                    $this->_resolvData($b, $key, $v_def); 
                }
            } 
        }
        return $this->_resolvData($data, $key, $v_def);
    }
    protected function _resolvData($data, $key, $v_def){
        if (is_null($data)){
            return null;
        }
        if($this->throwException && !igk_key_exists($data, $key)){
            throw new GraphQlSyntaxException(sprintf('missing property [%s]', $key));
        }
        $v = igk_getv($data, $key, $v_def);        
        return $v; 
        
    }
    /**
     * get data 
     * @param mixed $source 
     * @param null|callable $mapping 
     * @return mixed 
     * @throws IGKException 
     */
    public function getData($source, ?callable $mapping=null){ 
        if ($source instanceof IGraphQlIndexArray)
        {    
            $rtab = $source->to_array();
            return $this->_getIndexedData($rtab); 
        }
        if (is_array($source) && !igk_array_is_assoc($source)){
            return $this->_getIndexedData($source, $mapping); 
        }
        return $this->_getFieldData($source, $mapping);
    }
    protected function getModelMappingData(ModelBase $data, $mapping){
        $info = $data->getTableInfo();
        $tn = $info ? sysutil::GetModelTypeNameFromInfo($info): null; 
        if ($mapping && $tn){ 
            $map_data = $mapping($tn); 
            if ($map_data){
                $b = $data->map($map_data);
                return $b;
            }
        } 
        return $data->to_array();
    }
    private function _getFieldData($source, ?callable $mapping=null){
        $o = [];
        if ($source instanceof ModelsModelBase){
            // get mapping source 
            $source = $this->getModelMappingData($source, $mapping); 
        } else if ($source instanceof IGraphQlMappingData){
            $source = $source->getMappingData($mapping);
        }
        foreach($this->properties as $k=>$def){
            if ($def->child){
                // skip child definition property and wait to complete 
                continue;
            }
            $key = $def->alias ?? $def->name ?? $k;
            $o[$key] = $this->getValue($source, $def->name, $mapping);
        }
        return $o;
    }
    private function _getIndexedData(array $data, ?callable $mapping=null){
        $tab = [];
        while(count($data)>0){
            $q = array_shift($data);
            if (!$q){
                continue;
            }
            $o = $this->_getFieldData($q, $mapping);  
            $tab[] = $o;
        }  
        return $tab;
    }
    public function getModel(){ 
        return array_fill_keys(array_keys($this->properties), null); 
    }
    /**
     * is a dependency section
     * @return bool 
     */
    public function isDependOn():bool{
        return !is_null($this->parent);
    }
}