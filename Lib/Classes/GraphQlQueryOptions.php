<?php
// @author: C.A.D. BONDJE DOUE
// @file: GraphQlRequest.php
// @date: 20231005 18:41:44
namespace igk\io\GraphQl;

use IGK\System\IInjectable;
use IGKException;

///<summary></summary>
/**
* 
* @package igk\io\GraphQl
*/
class GraphQlQueryOptions implements IInjectable{
    private $m_options = [];
    private $m_callback; 

    /**
     * get context query|mutation
     * @var mixed
     */
    private $m_context;

    public function getContext(){
        return $this->m_context;
    }
    /**
     * set context type 
     * @param mixed $type 
     * @return void 
     */
    public function setContext($type){
  
        $trace = debug_backtrace(); 
        // Get the caller class name
        $callerClass = $trace[1]['class'];
        if (GraphQlParser::class == $callerClass)
        {
            $this->m_context = $type;
        }

    }

    /**
     * store the recen callable
     */
    public function setCallable($op){
        $this->m_callback = $op;
    }
    public function getCallable(){
        return $this->m_callback;
    }
    /**
     * 
     * @var ?GraphQlReadSectionInfo
     */
    private $m_section;

    /**
     * set the current calling section
     * @param null|GraphQlReadSectionInfo $section 
     * @return void 
     */
    public function setSection(? GraphQlReadSectionInfo $section){
        $this->m_section = $section;
    }
    public function getSection(){
        return $this->m_section;
    }
    /**
     * limit 
     * @var ?int|array
     */
    var $limit;
    /**
     * column order by
     * @var mixed
     */
    var $orderBy;
    /**
     * group by
     * @var mixed
     */
    var $groupBy;

    /**
     * query data 
     * @var mixed
     */
    var $data;

    public function clear(){
        $this->m_options = [];
    }
    /**
     * use store to store method. \
     * ovoid using 'options' as parameter
     * @param mixed $n 
     * @param mixed $v 
     * @return false|void 
     */
    public function store($n, $v){
        if ($n == 'options'){
            return false;
        }
        if (property_exists($this, $n)){
            $this->$n = $v;
        }else{
            $this->m_options[$n] = $v;
        }
        return true;
    }
    /**
     * get requested options paramaters
     * @param string $name 
     * @return mixed 
     * @throws IGKException 
     */
    public function __get(string $name){
        return igk_getv($this->m_options, $name);
    }
    public function getOptions(){
        return $this->m_options;
    }
    public function __construct(){
    } 

    public function stopIndexing(){
        if ($this->m_section){
            $this->m_section->stopIndexing();
        }
    }
    public function getCallback(){
        return $this->m_callback;
    }
}