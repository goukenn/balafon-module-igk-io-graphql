<?php
// @author: C.A.D. BONDJE DOUE
// @file: GraphQlSpreadInfo.php
// @date: 20230926 08:14:38
namespace igk\io\GraphQl;

use IGKEvents;

///<summary></summary>
/**
* use to load spread variable info 
* @package igk\io\GraphQl
*/
class GraphQlSpreadInfo{
    /**
     * source name of the spread operator 
     * @var string
     */
    private $m_name;
    /**
     * indexed key 
     * @var mixed
     */
    var $key;

    private $m_refOuput;
    private $m_entryData;
    public function __construct(GraphQlParser2 $reader, & $o, string $name, GraphQlPropertyInfo $property_info)
    {
        
        empty($name) && igk_die_exception(GraphQlException::class, 'name is empty');
        $this->m_name = $name;
        $v_fragment_info = null; 
        $v_fc = function($e)use($property_info){
           
            list($v_section, $data, & $outdata)=$e->args;
            if ($property_info->section !== $v_section){
                return;
            }

            $this->m_refOuput[] = [& $outdata, $data]; 
            unset($outdata);
        };
        $v_complete_fc = function ($e) use($reader,  & $v_fragment_info){
            list($v_reader) = $e->args;
            if ($v_reader !== $reader){
                return;
            }
            $outdata = & $this->m_refOuput;
            if (is_null($v_fragment_info)){
                $fragment = igk_getv($reader->getDeclaredInputs(), 'fragment'); 
                is_null($fragment) && igk_die_exception(GraphQlSyntaxException::class, 'missing fragment definition');
            
                $v_fragment_info = self::_array_find_first($fragment, function($i){
                    if ($i->name === $this->m_name){
                        return $i;
                    }
                }) ?? igk_die_exception(GraphQlSyntaxException::class, 'missing fragment');
            }
            $tab = [];
            $tq = $this->m_refOuput;
            $pos = null;
            while(count($tq)>0){
                $q = array_shift($tq);
                list(& $outdata, $data) = $q; 
                $this->m_entryData = $data;
                foreach($v_fragment_info->getFields() as $k=>$v){
                    $v_key = $v->getKey();
                    $tab[$v_key] = $this->getData($v);
                }

                $pos = $pos ?? array_search($this->key, array_keys($outdata));
                $t3 = array_slice($outdata, 0, $pos+1, true ) + $tab +  array_slice($outdata, $pos, count($outdata)-$pos, true );
                unset($t3[$this->key]);
                $outdata = $t3;

            }
            // update fields 
            // $outdata['number'] = 'number';
            // $outdata['street'] = 'street';
            // $outdata['box'] = 'box';  
            unset($outdata);
            $outdata = null;

        };

        igk_reg_hook(GraphQlHooks::HookName(GraphQlHooks::HOOK_END_ENTRY), $v_fc);
        igk_reg_hook(GraphQlHooks::HookName(GraphQlHooks::HOOK_LOAD_COMPLETE), $v_complete_fc);

       
        IGKEvents::UnregComplete(GraphQlHooks::HookName(GraphQlHooks::HOOK_LOAD_COMPLETE), 
        function()use(& $v_fc, & $v_complete_fc){
            igk_unreg_hook(GraphQlHooks::HookName(GraphQlHooks::HOOK_END_ENTRY), $v_fc);
            igk_unreg_hook(GraphQlHooks::HookName(GraphQlHooks::HOOK_LOAD_COMPLETE), $v_fc);
        });
    }
    public function getData($g){
        return igk_getv($this->m_entryData, $g->name); 
    }
    
    // public function update(& $o, $types){
    //     $o[$this->m_name] = $types[$this->m_name];
    // }
    private static function _array_find_first($tab, $callback){
        foreach ($tab as $value) {
            if (($p =$callback($value))!==null){
                return $p;
            }
        }
    }
    // /**
    //  * fragment to update at end list 
    //  * @param mixed $parser 
    //  * @param mixed $k 
    //  * @param mixed $o 
    //  * @param mixed $data 
    //  * @param mixed $t_entry 
    //  * @param mixed $callback 
    //  * @return void 
    //  */
    // public function updateFields($parser, $k, & $o, $data, $t_entry, $callback){
    //     igk_reg_hook(  GraphQlParser::HOOK_LOADING_COMPLETE, function($e)use($parser, $k, & $o, $data, $t_entry, $callback){
    //         $tparser =  $e->args[0];
    //         if ($parser !== $tparser){
    //             return;
    //         }
    //         $fragments = $parser->getDeclaredInputs()['fragment'];
    //         unset($o[$k]);
    //         $f = self::_array_find_first($fragments, function($i){
    //             if ($i->name === $this->m_name){
    //                 return $i;
    //             }
    //         });
    //         foreach($f->getFields() as $tf){
    //             $o[$tf] = $data->getValue($tf, $t_entry, $callback);
    //         }
    //     });
    // }
}