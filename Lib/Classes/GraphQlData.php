<?php
// @author: C.A.D. BONDJE DOUE
// @file: GraphQlData.php
// @date: 20221106 08:26:16
namespace igk\io\GraphQl;

use igk\io\GraphQl\GraphQlData as GraphQlGraphQlData;
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
     * the name of the graph ql string data 
     * @var mixed
     */
    var $name;
    
    /**
     * is indexed 
     * @var bool
     */
    private $m_is_index = false;
    /**
     * data store 
     * @var array
     */
    private $m_info = [];

    /**
     * is provided
     * @var false
     */
    private $m_isProvided = false;


    /**
     * this store graph data
     * @return mixed 
     */
    public function isProvided():bool{
        return $this->m_isProvided;
    }
    public function first(){
        return $this->m_is_index ? array_values($this->entry)[0] : $this->entry;
    }
    public static function Create($data){
        $v_d = new self;
        $v_d->storeEntry($data);
        return $v_d;
    }
    /**
     * store entry
     * @param mixed $data 
     * @return void 
     */
    public function storeEntry($data){
        $this->entry = $data;
        $this->m_is_index = ($data && is_array($data) && !igk_array_is_assoc($data));
        $this->m_isProvided = true;
    }
    /**
     * get if indexed 
     * @return bool 
     */
    public function isIndex(): bool{
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
        if (!$this->m_isProvided){
            igk_die("no data provided");
        }  
        $f_info = igk_getv($this->m_info, $key);
        $v_def = $f_info ? $f_info->default: null;

        if ($data instanceof IGraphQlMappingData){
            $v = $data->getMappingValue($key, $v_def);
        }
        else if ($data instanceof ModelBase){
            $info = $data->getTableInfo();
            $tn = sysutil::GetModelTypeNameFromInfo($info);
            $map_data = $mapping($tn); // $this->getMapData($tn);
            if ($map_data){
                $b = $data->map($map_data);
                $v = igk_getv($b, $key,  $v_def);
            }
        }
        else{
            $v = igk_getv($data, $key, $v_def);
        } 
        return $v; 
    }
    /**
     * store data info 
     * @param mixed $key 
     * @param mixed $info 
     * @return void 
     */
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

    private function _updateArrayField(& $o, array $other, ?callable $callback=null){
            // copy data
            $data = $this;
            $tab = [$o];
            $model = array_keys($o);

            while (count($other) > 0) {
                $rp = array_shift($other);
                $i = [];
                foreach ($model as $k) {
                    $i[$k] = $data->getValue($k, $rp,$callback);
                }
                $tab[] = $i;
            }
            // + | change the reference of  
            $o = $tab;
            // $data->storeEntry(new GraphQlEmptyEntry);
    }
    public function updateObjectField(& $o, ?string $path=null, ?callable $callback=null){
        $data = $this;
        if ($data->isIndex()) {
            // o is the modele
            $other = array_slice($data->entry, 1);
            $this->_updateArrayField($o, $other, $callback);
        } else {
            if ($path){
                $obj = igk_conf_get($data->entry, $path);
                if ($obj){
                    if (is_array($obj)){
                        $other = array_slice($obj, 1);
                        $this->_updateArrayField($o, $other, $callback);
                    }
                }
            }
        }
    }
    public static function IsIndexed($data):bool{
        if ($data instanceof GraphQlData){
            return $data->isProvided() && $data->isIndex();
        } 
        return (is_array($data) && !igk_array_is_assoc($data));
    }
}