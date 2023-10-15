<?php
// @author: C.A.D. BONDJE DOUE
// @file: GraphQlSectionDefinitionReader.php
// @date: 20231013 09:06:42
namespace igk\io\GraphQl;


use igk\io\GraphQl\GraphQlReaderConstants as rConst;
use igk\io\GraphQl\Helper\GraphQlReaderUtils;
use igk\io\GraphQl\System\IO\Traits\GraphQlSectionReaderTrait;
use igk\io\GraphQl\Traits\GraphQlPropertyReadingTrait;

///<summary></summary>
/**
* use for reading section property info
* @package igk\io\GraphQl
*/
class GraphQlSectionDefinitionReader{
    var $reader;
    var $noThrowOnMissingProperty;
    private $m_o; // <- reference object to update
    private $m_source_data;
    private $m_alias_name;
    private $m_property_name;
    private $m_desc; 
    private $m_current_pointer;

    use GraphQlPropertyReadingTrait;
    use GraphQlSectionReaderTrait;


    public function __construct(GraphQlParser $reader, & $o, $data=null){
        $this->reader = $reader;
        $this->m_source_data = $data;
        $this->m_o = $o;
    }

    /**
     * 
     * @param mixed $tokenid 
     * @param mixed $v 
     * @param null|GraphQlReadSectionInfo $v_section_info 
     * @param null|GraphQlPropertyInfo $v_property_info 
     * @return void 
     */
    public function readerInfo($tokenid, $v, ?GraphQlReadSectionInfo & $v_section_info, ?GraphQlPropertyInfo & $v_property_info, $data = null){
        $v_desc = & $this->m_desc;
        $v_o = & $this->m_o;
        $v_current_pointer = & $this->m_current_pointer;

        switch($tokenid){
            case rConst::T_READ_ALIAS:
                if ($v_property_info){
                    if (!empty($this->m_alias_name)){
                        throw new GraphQlSyntaxException('already set alias');
                    }
                } 
                $this->m_alias_name = $v;
                break;
            case rConst::T_READ_NAME:
                // update _property_
                $v_property_info ?? $this->_updateLastProperty($v_o, $v_property_info, $data);
               
                $this->m_property_name = $v;
                $v_property_info = new GraphQlPropertyInfo($v);
                $v_property_info->description = $this->m_desc;
                $v_property_info->alias = $this->m_alias_name;
                $v_property_info->section = $v_section_info;  // <- chain section
                $v_section_info->properties[$v] = $v_property_info;
                $this->m_desc = null;
                $this->m_alias_name = null;
                break;
            case rConst::T_READ_DESCRIPTION:
                $this->m_desc = $v;
                break;
            case rConst::T_READ_COMMENT:
                GraphQlReaderUtils::BindComment($this->reader, $v);
                break;
            case rConst::T_READ_INLINE_SPEAR:
                $v_property_info && $this->_updateLastProperty($o, $v_property_info, $data);

                $this->_bind_inline_spear($this->reader, $v_section_info, $v_desc, $o);
                $v_desc = null;
                $v_property_info = null; 
                break; 

            case rConst::T_READ_START:
                $v_section_info = $this->_n_chain_section($this->reader, $v_section_info, $v_o, $v_current_pointer);
                if ($v_property_info){
                    $v_property_info->hasChild = true;
                    $v_property_info->setChildSection(   $v_section_info);
                } 
                $v_property_info = null; 
                break;
            case rConst::T_READ_END: 
                $v_section_info = $v_section_info ? $v_section_info->parent : null;
                $v_property_info = null;
                break;

           
        }
    }

    public function _chainSection(){
        // To implement
    }
}