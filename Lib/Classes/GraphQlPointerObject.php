<?php
// @author: C.A.D. BONDJE DOUE
// @file: IGraphQlPointerObject.php
// @date: 20231010 19:06:07
namespace igk\io\GraphQl;


///<summary></summary>
/**
* use to manage pointer on array with parent
* @package igk\io\GraphQl
*/
class GraphQlPointerObject{
    private $m_obj;
    private $m_p;
    public function __construct(array & $obj, ?GraphQlPointerObject $p=null){
        $this->m_obj = & $obj;
        $this->m_p  =$p;
    } 
    public function & getRefData(){
        return $this->m_obj;
    }
    public function getParentRef(){
        return $this->m_p;
    }
    /**
     * clean the pointer 
     * @return void 
     */
    public function clean(){
        $this->m_obj = [];
    }
    /**
     * free pointer from source object
     * @return void 
     */
    public function unset(){
        unset($this->m_obj);
        $this->m_obj = [];
    }
}