<?php
// @author: C.A.D. BONDJE DOUE
// @file: GraphQlPropertyReadingTrait.php
// @date: 20231013 09:18:43
namespace igk\io\GraphQl\Traits;
 
use igk\io\GraphQl\GraphQlParser;
use igk\io\GraphQl\GraphQlPropertyInfo;
use igk\io\GraphQl\GraphQlReadSectionInfo; 
use igk\io\GraphQl\IGraphQlParserOptions;

///<summary></summary>
/**
* 
* @package igk\io\GraphQl\Traits
*/
trait GraphQlPropertyReadingTrait{
   
    protected function _updateLastProperty(& $o, ?GraphQlPropertyInfo $v_property_info , $data){
        if (is_null($v_property_info)){
            return;
        }
        $v_key = $v_property_info->getKey(); 
        $o[$v_key] = $v_property_info;

        // if (!$v_property_info->section->isDependOn()) {
        //     // update property info with field - 
        //     if (($data instanceof GraphQlData) && $data->isProvided() && $data->isIndex()) {
        //         $o[$v_key] = $v_property_info->default;
        //         return;
        //     }

        //     if ($data) {
        //         $this->_update_single_property($o, $v_property_info, $data);
        //     } else { 
        //         $o[$v_key] = $v_property_info->default;
        //     }
        // } else {
        //     $o[$v_key] = $v_property_info;
        // }
    }
   
}