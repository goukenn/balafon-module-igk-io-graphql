<?php
// @author: C.A.D. BONDJE DOUE
// @file: GraphQlSectionReaderTrait.php
// @date: 20231014 10:26:25
namespace igk\io\GraphQl\System\IO\Traits;

use igk\io\GraphQl\GraphQlParser;
use igk\io\GraphQl\GraphQlPointerObject;
use igk\io\GraphQl\GraphQlPropertyInfo;
use igk\io\GraphQl\GraphQlReadSectionInfo;
use igk\io\GraphQl\Types\InlineSpread;

///<summary></summary>
/**
* 
* @package igk\io\GraphQl\System\IO\Traits
*/
trait GraphQlSectionReaderTrait{
    protected function _n_update_last_property(& $o, ?GraphQlPropertyInfo $v_property_info, GraphQlReadSectionInfo $attach_to_section, $refData=null, $keyName=null){
        if ($v_property_info){
             $v_key = $keyName ?? $v_property_info->getKey();
             $o[$v_key] = $refData ?? $v_property_info;
             $attach_to_section->properties[$v_key] = $v_property_info;
             $v_property_info->section = $attach_to_section;
        }
     }
     protected function _n_chain_section(GraphQlParser $reader, ?GraphQlReadSectionInfo $v_section_info,
     & $o, ?GraphQlPointerObject & $v_current_pointer = null
     ){
         $v_current_pointer = new GraphQlPointerObject($o, $v_current_pointer); 
         $n = new GraphQlReadSectionInfo($reader, $v_current_pointer);
         $n->parent = $v_section_info;
         $n->copyState($this);
         return $n;
     }

     protected function _bind_inline_spear(GraphQlParser $reader, GraphQlReadSectionInfo $section_info, ?string $description, &$refObject){
        $v_inf = new InlineSpread($reader, $section_info, $refObject);
        $v_inf->description = $description;
        $v_inf->readDefinition($reader);
    }
    
}