<?php
// @author: C.A.D. BONDJE DOUE
// @file: GraphQlReferenceSectionProperty.php
// @date: 20231010 21:01:50
namespace igk\io\GraphQl;

use Closure;

///<summary></summary>
/**
* 
* @package igk\io\GraphQl
*/
class GraphQlReferenceSectionProperty{
    /**
     * 
     * @var GraphQlReadSectionInfo
     */
    private $m_section;

    public function __construct($reader, & $o, $key, GraphQlReadSectionInfo $section_info, GraphQlPropertyInfo $propery_info, ?callable $mapping=null)
    {
        $this->m_section = $section_info;
        $fc = function($e)use(& $fc, & $o, $reader, $key, $section_info,  $propery_info, $mapping){
            //for GraphQlHooks::HOOK_END_SECTION
            list($v_reader, $section, $data, $sourceMap) = $e->args;
            if (($v_reader!== $reader) || ($section_info!==$section)){
                return;
            }
            if ($propery_info->hasChild && !is_null($sourceMap)){
                // is child property so it group source data
                $o[$key] = $sourceMap;
            }else{
                // build section object description
                $tb = [];
                $tb = $section_info->getData($data, $mapping, $sourceMap);
                $o[$key] = $tb;
            }
            igk_unreg_hook(GraphQlHooks::HookName(GraphQlHooks::HOOK_END_SECTION),$fc);
        };
        $e_fc = function($e)use(& $e_fc, & $o, $reader, $key, $section_info, $propery_info, $mapping){
            //for GraphQlHooks::HOOK_END_ENTRY
            list($section, $data, & $output) = $e->args;
            if ( ($section_info!==$section)){
                return;
            } 
        };
        
        $v_unreg_complete = function()use(& $e_fc, &  $v_unreg_complete, & $t_fc){
            igk_unreg_hook(GraphQlHooks::HookName(GraphQlHooks::HOOK_END_ENTRY),$e_fc);
            igk_unreg_hook(GraphQlHooks::HookName(GraphQlHooks::HOOK_LOAD_COMPLETE), $v_unreg_complete);
            igk_unreg_hook(GraphQlHooks::HookName(GraphQlHooks::HOOK_END_QUERY), $t_fc);
        };

        igk_reg_hook(GraphQlHooks::HookName(GraphQlHooks::HOOK_LOAD_COMPLETE), $v_unreg_complete);


        // for query invoke method function dans scope data
        // $e_func_c = function($e)use(& $e_func_c, & $o, $reader, $key, $section_info, $propery_info, $mapping){
        //     //for hook fsection func
        //     list($v_reader, $section, $data) = $e->args;
        //     if (($v_reader!== $reader) || ($section_info!==$section)){
        //         return;
        //     }
        //     $tb = $reader->invokeListenerMethod($propery_info->name, $propery_info->args);
        //     $o[$key] = $section->getData($tb, $mapping);
        //     igk_unreg_hook(GraphQlHooks::HookName(GraphQlHooks::HOOK_SECTION_FUNC),$e_func_c);
        // };

        if ($propery_info->type != 'func'){
            igk_reg_hook(GraphQlHooks::HookName(GraphQlHooks::HOOK_END_SECTION), $fc);
            igk_reg_hook(GraphQlHooks::HookName(GraphQlHooks::HOOK_END_ENTRY), $e_fc);
        }
        // if ($propery_info->type == 'func'){
        //     igk_reg_hook(GraphQlHooks::HookName(GraphQlHooks::HOOK_SECTION_FUNC), $e_func_c);
        // }

        // $t_fc = Closure::fromCallable([$this,'_bindAndDispatch'])->bindTo($this); 
        // igk_reg_hook(GraphQlHooks::HookName(GraphQlHooks::HOOK_END_QUERY), $t_fc);
    }
    protected function _bindAndDispatch($e){
        $e->args;
        igk_wln(__FILE__.":".__LINE__ , 'bind and dispach source data');
    }
    public function getData($data){
        $tb= $this->m_section->getData($data);
        return $tb;
    }
}