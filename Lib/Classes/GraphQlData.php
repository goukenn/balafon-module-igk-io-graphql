<?php
// @author: C.A.D. BONDJE DOUE
// @file: GraphQlData.php
// @date: 20221106 08:26:16
namespace igk\io\GraphQl;

use IGK\Models\ModelBase;
use IGKException;
use IGKSysUtil as sysutil;

///<summary></summary>
/**
* data host
* @package igk\io\GraphQl
*/
class GraphQlData{

    /**
     * list of entries to bind
     * @var mixed
     */
    var $entry;
    /**
     * is indexed 
     * @var bool
     */
    var $m_is_index = false;
    /**
     * the name of the graph ql string data 
     * @var mixed
     */
    var $name;

    /**
     * data store 
     * @var array
     */
    private $m_info = [];
    public function first(){
        return $this->m_is_index ? array_values($this->entry)[0] : $this->entry;
    }
    public static function Create($data){
        $v_d = new self;
        $v_d->storeEntry($data);
        return $v_d;
    }
    public function storeEntry($data){
        $this->entry = $data;
        $this->m_is_index = ($data && is_array($data) && !igk_array_is_assoc($data));
    }
    public function isIndex(){
        return $this->m_is_index;
    }

    /**
     * get data values
     * @param mixed $key 
     * @param mixed $data 
     * @return mixed 
     * @throws IGKException 
     */
    public function getValue($key, $data, callable $mapping){        
        $f_info = $this->m_info[$key];

        if ($data instanceof IGraphQlMappingData){
            $v = $data->getMappingValue($key, $f_info->default);
        }
        else if ($data instanceof ModelBase){
            $info = $data->getTableInfo();
            $tn = sysutil::GetModelTypeNameFromInfo($info);
            $map_data = $mapping($tn); // $this->getMapData($tn);
            if ($map_data){
                $b = $data->map($map_data);
                $v = igk_getv($b, $key,  $f_info->default);
            }
        }
        else{
            $v = igk_getv($data, $key, $f_info->default);
        }

        return $v;// igk_getv($data, $key, $f_info->default);
    }
    public function storeInfo($key, $info){
        $this->m_info[$key] = $info;
    }
    /**
     * get graph info
     * @return array 
     */
    public function getInfo(){
        return $this->m_info;
    }
}