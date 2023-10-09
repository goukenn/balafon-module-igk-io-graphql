<?php
// @author: C.A.D. BONDJE DOUE
// @file: GraphQlSpreadInfo.php
// @date: 20230926 08:14:38
namespace igk\io\GraphQl;


///<summary></summary>
/**
* 
* @package igk\io\GraphQl
*/
class GraphQlSpreadInfo{
    private $m_name;
    public function __construct(string $name)
    {
        $this->m_name = $name;
    }
    public function update(& $o, $types){
        $o[$this->m_name] = $types[$this->m_name];
    }
    private static function _array_find_first($tab, $callback){
        foreach ($tab as $value) {
            if (($p =$callback($value))!==null){
                return $p;
            }
        }
    }
    /**
     * fragment to update at end list 
     * @param mixed $parser 
     * @param mixed $k 
     * @param mixed $o 
     * @param mixed $data 
     * @param mixed $t_entry 
     * @param mixed $callback 
     * @return void 
     */
    public function updateFields($parser, $k, & $o, $data, $t_entry, $callback){
        igk_reg_hook(GraphQlParser::HOOK_LOADING_COMPLETE, function($e)use($parser, $k, & $o, $data, $t_entry, $callback){
            $tparser =  $e->args[0];
            if ($parser !== $tparser){
                return;
            }
            $fragments = $parser->getDeclaredInputs()['fragment'];
            unset($o[$k]);
            $f = self::_array_find_first($fragments, function($i){
                if ($i->name === $this->m_name){
                    return $i;
                }
            });
            foreach($f->getFields() as $tf){
                $o[$tf] = $data->getValue($tf, $t_entry, $callback);
            }
        });
    }
}