<?php
// @author: C.A.D. BONDJE DOUE
// @file: GraphQlReadSectionInfo.php
// @date: 20231009 17:41:29
namespace igk\io\GraphQl;

use Closure;
use IGK\Database\Mapping\Traits\ModelMappingDataTrait;
use igk\io\GraphQl\GraphQlReadSectionInfo as GraphQlGraphQlReadSectionInfo;
use igk\io\GraphQl\Helper\GraphQlReaderUtils;
use igk\io\GraphQl\Traits\GraphQlReadCommentOptionsTrait;
use igk\io\GraphQl\Types\InlineSpread;
use IGK\Models\ModelBase;
use IGK\System\Exceptions\ArgumentTypeNotValidException;
use IGK\System\Models\ModelBase as ModelsModelBase;
use IGKException;
use IGKSysUtil as sysutil;
use ReflectionException;

///<summary></summary>
/**
* information used to read brank section 
* @package igk\io\GraphQl
*/
class GraphQlReadSectionInfo extends GraphQlSectionProperty{
    use GraphQlReadCommentOptionsTrait;
    use ModelMappingDataTrait;
    private $m_reader;
    private $m_pointer;
    protected $_is_indexing;
    private $m_sourceTypeName;
    private $m_source_data;
    
    public function stopIndexing(){
        $q = $this;
        while($q){
            if ($q->_is_indexing ){

                $q->_is_indexing = false;
                break;
            }
            $q = $q->parent;
        }
        $this->_is_indexing =false;
    }
    function __debugInfo()
    {
        return [];
    }
    /**
     * get the source type name
     * @return mixed 
     */
    public function getSourceTypeName()
    {
        $q = $this;
        while($q){
            if (!empty($q->m_sourceTypeName)){
                return $q->m_sourceTypeName;
            }
            $q = $q->parent;
        }
        return null;
    }
 
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
     * mo
     * @param mixed $data 
     * @param mixed $key 
     * @param null|callable $mapping model mapping data
     * @return mixed 
     * @throws IGKException 
     */
    public function getValue($data, $key, ?callable $mapping, $optional=false){
         
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
                    // $b = $data->map($map_data); 
                    $data = $data->map($map_data); 
                    //return $this->_resolvData($b, $key, $v_def); 
                }
            } 
        }
        // igk_wln_e("the data", $data, $key);
        return $this->_resolvData($data, $key, $v_def, $optional);
    }
    /**
     * 
     * @param null|string $typename 
     * @return $this 
     */
    public function setSourceTypeName(?string $typename){
        $this->m_sourceTypeName = $typename;
        return $this;
    }
    /**
     * 
     * @param mixed $data 
     * @param mixed $key 
     * @param mixed $v_def 
     * @param bool $optional 
     * @return mixed 
     * @throws GraphQlSyntaxException 
     * @throws IGKException 
     */
    protected function _resolvData($data, $key, $v_def, $optional=false){
        if (is_null($data)){
            return null;
        }
        if($data && !$optional && !$this->noThrowOnMissingProperty && !igk_key_exists($data, $key)){
             throw new GraphQlSyntaxException(sprintf('missing property [%s]', $key));
        }
        $v = igk_getv($data, $key, $v_def);  
        // + | --------------------------------------------------------------------
        // + | check for closure d 
        // + |
        
        //  + | system pass closure so
        if (($v instanceof Closure)){
            $newv = $v();
            $this->m_reader->updateSourceData($data, $key, $newv);
            return $newv;
        }      
        return $v; 
        
    }
    /**
     * get path to current section
     * @return null|string 
     */
    public function getFullPath(): ?string{
        $p = []; 
        $q = $this->parent;
        while($q){
            array_unshift($p, $q->name);
            $q = $q->parent; 
        }
        return implode('/', array_filter($p));
    }
    /**
     * 
     * @param mixed $source_data 
     * @param mixed $entry_data 
     * @return null|array 
     * @throws IGKException 
     */
    public function getReferencedData($source_data, $entry_data){
        $this->m_source_data = $entry_data;
        $cp = $this->getData($source_data); 
        $this->m_source_data = null;
        return $cp;
    }
  
    

    public static function InvokeListenerMethod(GraphQlParser $reader, $def, $v_core_data=null, $mapping=null, $type_call='query'){
        $section = $def->getChildSection(); 
        $data = $reader->invokeListenerMethod($def->name, $def->args, $v_core_data, $section, $type_call);
        if ($data){
            $ndata = $section->_getData($data, $mapping);
        } else 
            $ndata = $def->default;  
        return $ndata;  
    }
  

    protected function _chainNullSourceMap($sourceMap, $default=null){
        $o = [];
        $q = [['n'=>& $o, 'p'=>$sourceMap]];
        while(count($q)>0){
            $tp = array_shift($q);
            $n = & $tp['n'];
            $sourceMap = $tp['p']; 

            $k = array_key_first($sourceMap);
            $tq = array_shift($sourceMap);

            if ($tq instanceof GraphQlPropertyInfo){
                if ($sourceMap){
                   // array_unshift($q, ['n'=>& $n, 'p'=>$sourceMap]);
                }
                $tk = $tq->getKey();
                $n[$tk] = $default; 
            } else {
                $n[$k] = $default;
            }

        }
        return $o;

    }

    public function inSubchainOf(GraphQlGraphQlReadSectionInfo $section){
        if ($section === $this){
            return true;
        }
        $q = $this;
        while($q){
            $q = $q->parent;
            if ($q === $section)
                return true;
        }
        return false;
    }
  
    public function getModel(){ 
        return array_fill_keys(array_keys($this->properties), null); 
    }
    public function __construct(GraphQlParser $reader, GraphQlPointerObject $pointer){
        $this->m_reader = $reader;
        $this->m_pointer = $pointer;
    }   
    /**
     * 
     * @return GraphQlPointerObject 
     */
    public function getRefPointer(){
        return $this->m_pointer;
    }

    /**
     * is a dependency section
     * @return bool 
     */
    public function isDependOn():bool{
        return !is_null($this->parent);
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
    protected function _getFieldData($source, ?callable $mapping=null){
        $o = new GraphQlReferenceArrayObject; 
        if ($source instanceof ModelsModelBase){
            // get mapping source 
            $source = $this->getModelMappingData($source, $mapping); 
        } else if ($source instanceof IGraphQlMappingData){
            $source = $source->getMappingData($mapping);
        }
        $path = $this->getFullPath();
        $v_core_data = $path && $source ? igk_conf_get($source, $path) : $source;
        foreach($this->properties as $k=>$def){
          
            $data = $v_core_data;
            if (is_numeric($k)){ 
                $o[$k] = null;
                // * mark field no be updated by graph listener 
                if ($def instanceof IGraphQlUpdateProperty)  
                    $def->UpdateRefObject($o, $data, $this->m_source_data ?? $source);

                continue;
            }
            $key = $def->getKey() ?? $k; 
            if (is_null($def)){
                $o[$key] = null;
                continue;
            }

            if ($def->isReservedProperty()){
                $o[$key] = GraphQlReaderUtils::GetReservedValue($def); 
                continue;
            }


            if ($def->type == GraphQlPropertyInfo::TYPE_FUNC){ 
                $ndata = self::InvokeListenerMethod($this->m_reader, $def, $v_core_data);

             
                $o[$key]= $ndata;
                unset($n_child);  
                continue;
            }
            if ($def->type == GraphQlPropertyInfo::TYPE_SPEAR){ 
                $o[$k] = new GraphQlSpreadInfo($this->m_reader, $o, $k, $def, $data);
                continue;
            }

            $v = $this->getValue($data, $def->name, $mapping, $def->optional);

            if ($def->hasChild && $v){ 
                $section = $def->getChildSection();
                $ndata = $section->getData($v, $mapping);
                $o[$key] = $ndata;
                continue;
            }

            $o[$key] = $v;
        }
        return $o;
    }
}