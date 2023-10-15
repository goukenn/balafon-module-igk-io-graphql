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
class GraphQlSpreadInfo implements IGraphQlProperty{
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

    private $is_single = true;

    private $m_refOuput;
    private $m_entryData;
    private $m_property_info;

    public function & getRefOutput(){
        return $this->m_refOuput;
    } 

    public function __construct(GraphQlParser $reader, GraphQlReferenceArrayObject $o, string $name, GraphQlPropertyInfo $property_info, $soureData = null)
    {
        
        empty($name) && igk_die_exception(GraphQlException::class, 'name is empty');
        $this->key = $name;
        $this->m_name = trim($name,'.');
        $this->m_refOuput = $o;
        $this->m_entryData = $soureData;
        $this->m_property_info = $property_info;
        
        $v_fragment_info = null; 
      

        $v_complete_fc = function ($e) use($reader,  & $v_fragment_info, $property_info){
            list($v_reader) = $e->args;
            if ($v_reader !== $reader){
                return;
            } 
            $outdata = $this->m_refOuput;
            // $fields = GraphQlReadSectionInfo::$sharedProperties;
            // //igk_wln_e("check is ook ", self::$sm_sharedData === $outdata);
            // $tab1 = & $fields['shared:1'];
            // $tab2 = & $fields['shared:2'];

            if (is_null($v_fragment_info)){
                $fragment = igk_getv($reader->getDeclaredInputs(), 'fragment'); 
                is_null($fragment) && igk_die_exception(GraphQlSyntaxException::class, 'missing fragment definition');
            
                $v_fragment_info = self::_array_find_first($fragment, function($i){
                    if ($i->name === $this->m_name){
                        return $i;
                    }
                }) ?? igk_die_exception(GraphQlSyntaxException::class, 'missing fragment');
            }
            if (!$this->onType($v_fragment_info)){
                unset($outdata[$this->key]);
                unset($outdata);
                return;
            }


            $tab = [];
            $tq = [];
            $pos = null;
            if ($this->is_single){
                $tq = [[$this->m_refOuput, $this->m_entryData]];
            }
            while(count($tq)>0){
                $q = array_shift($tq);
                list($outdata, $data) = $q; 
                $this->m_entryData = $data;
                foreach($v_fragment_info->getFields() as $k=>$v){
                    $v_key = $v->getKey();
                    $tab[$v_key] = $this->getData($v);
                } 
                $outdata->replaceWith($tab, $this->key);
                // $pos = $pos ?? array_search($this->key, array_keys($outdata));
                // $t3 = array_slice($outdata, 0, $pos+1, true ) + $tab +  array_slice($outdata, $pos, count($outdata)-$pos, true );
                // unset($t3[$this->key]);
                // $outdata = $t3;
                // unset($outdata);
                // $outdata = null;

            }
            // update fields 

        };

        // igk_reg_hook(GraphQlHooks::HookName(GraphQlHooks::HOOK_END_ENTRY), $v_fc);
        igk_reg_hook(GraphQlHooks::HookName(GraphQlHooks::HOOK_LOAD_COMPLETE), $v_complete_fc);

       
        IGKEvents::UnregComplete(GraphQlHooks::HookName(GraphQlHooks::HOOK_LOAD_COMPLETE), 
        function()use(& $v_fc, & $v_complete_fc){
            // igk_unreg_hook(GraphQlHooks::HookName(GraphQlHooks::HOOK_END_ENTRY), $v_fc);
            igk_unreg_hook(GraphQlHooks::HookName(GraphQlHooks::HOOK_LOAD_COMPLETE), $v_fc);
        });
    }
    public function getData($g){
        return igk_getv($this->m_entryData, $g->name); 
    }

    public function onType($fragment):bool{
        return $fragment->onType($this->m_property_info->section); 
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