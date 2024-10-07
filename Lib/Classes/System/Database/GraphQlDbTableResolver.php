<?php
// @author: C.A.D. BONDJE DOUE
// @file: GraphQlDbTableResolver.php
// @date: 20231016 12:42:06
namespace igk\io\GraphQl\System\Database;

use Closure;
use IGK\Controllers\BaseController;
use igk\io\GraphQl\GraphQlQueryOptions;
use igk\io\GraphQl\System\Database\Helpers\GraphQlDbHelper;

///<summary></summary>
/**
* 
* @package igk\io\GraphQl\System\Database
*/
class GraphQlDbTableResolver{
    private $m_ctrl;
    private $m_info;
    private $m_extra;
    public function __debugInfo()
    {
        return [];
    }
    static $sm_map_keys;
    public static function GetMapKeys($info){
        if (is_null(self::$sm_map_keys)){
            self::$sm_map_keys = [];
        }
        $key = $info->modelClass;
        if (!isset(self::$sm_map_keys[$key])){

            self::$sm_map_keys[$key] = GraphQlDbHelper::InitMapKeys($info); 
        }
        
        return self::$sm_map_keys[$key]; 
    }
    public function __invoke(GraphQlQueryOptions $options=null)
    {
        $cl = $this->m_info->modelClass;
        $model = $cl::model();
        if ($lt = $model->select_all()){
            $mapkeys = self::GetMapKeys($this->m_info); 
            $data = array_map(function($row) use($mapkeys){
                return $row->map($mapkeys); 
            }, $lt ); 
            return $data;
        }
        return null; 
    }
    public function __construct(BaseController $ctrl, $info, $d)
    {
        $this->m_ctrl = $ctrl;
        $this->m_info = $info;
        $this->m_extra = $d;
    }
    /**
     * is a closure 
     * @return Closure(): mixed 
     */
    public function resolver(){
        return function(...$args){
            return $this(...$args); 
        };
    }
}