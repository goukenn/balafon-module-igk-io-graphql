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
}