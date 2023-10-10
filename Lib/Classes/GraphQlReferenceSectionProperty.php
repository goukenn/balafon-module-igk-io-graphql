<?php
// @author: C.A.D. BONDJE DOUE
// @file: GraphQlReferenceSectionProperty.php
// @date: 20231010 21:01:50
namespace igk\io\GraphQl;


///<summary></summary>
/**
* 
* @package igk\io\GraphQl
*/
class GraphQlReferenceSectionProperty{
    public function __construct($reader, & $o, $key, GraphQlReadSectionInfo $section_info, $propery_info, ?callable $mapping=null)
    {
        $fc = function($e)use(& $fc, & $o, $reader, $key, $section_info, $propery_info, $mapping){
            //for 
            list($v_reader, $section, $data) = $e->args;
            if (($reader!== $reader) || ($section_info!==$section)){
                return;
            }
            $tb = [];
            $tb = $section_info->getData($data, $mapping);
            $o[$key] = $tb;

            igk_unreg_hook(GraphQlHooks::HookName(GraphQlHooks::HOOK_END_INFO),$fc);
        };
        $e_fc = function($e)use(& $e_fc, & $o, $reader, $key, $section_info, $propery_info, $mapping){
            //for
            list($v_reader, $section, $data) = $e->args;
            if (($v_reader!== $reader) || ($section_info!==$section)){
                return;
            }
            igk_unreg_hook(GraphQlHooks::HookName(GraphQlHooks::HOOK_END_ENTRY),$e_fc);
        };


        $e_func_c = function($e)use(& $e_func_c, & $o, $reader, $key, $section_info, $propery_info, $mapping){
            //for hook fsection func
            list($v_reader, $section, $data) = $e->args;
            if (($v_reader!== $reader) || ($section_info!==$section)){
                return;
            }
            $tb = $reader->invokeListenerMethod($propery_info->name, $propery_info->args);
            $o[$key] = $section->getData($tb, $mapping);
            igk_unreg_hook(GraphQlHooks::HookName(GraphQlHooks::HOOK_SECTION_FUNC),$e_func_c);
        };

        if ($propery_info->type != 'func'){
            igk_reg_hook(GraphQlHooks::HookName(GraphQlHooks::HOOK_END_INFO), $fc);
            igk_reg_hook(GraphQlHooks::HookName(GraphQlHooks::HOOK_END_ENTRY), $e_fc);
        }
        if ($propery_info->type == 'func'){
            igk_reg_hook(GraphQlHooks::HookName(GraphQlHooks::HOOK_SECTION_FUNC), $e_func_c);
        }
    }
}