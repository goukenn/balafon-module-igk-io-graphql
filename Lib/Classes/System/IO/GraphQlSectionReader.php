<?php
// @author: C.A.D. BONDJE DOUE
// @file: GraphQlSectionReader.php
// @date: 20231014 10:18:22
namespace igk\io\GraphQl\System\IO;

use igk\io\GraphQl\GraphQlException;
use igk\io\GraphQl\GraphQlParser;
use igk\io\GraphQl\GraphQlPropertyInfo;
use igk\io\GraphQl\Traits\GraphQlReadCommentOptionsTrait;
use igk\io\GraphQl\GraphQlReaderConstants;
use igk\io\GraphQl\GraphQlSyntaxException;
use igk\io\GraphQl\System\IO\Traits\GraphQlSectionReaderTrait;


///<summary></summary>
/**
 * Query read graph - 
 * @package igk\io\GraphQl\System\IO
 */
class GraphQlSectionReader
{
    use GraphQlSectionReaderTrait;
    use GraphQlReadCommentOptionsTrait;
    private $m_reader;

    public function __construct(GraphQlParser $reader)
    {
        $this->m_reader = $reader;
    }
    /**
     * read section 
     * @return false|array
     */
    public function read()
    {
        $v_root = null;
        $v_o = null;
        $v_brank = 0;
        $v_alias = null;
        $v_section_info = null;
        $v_property_info = null;
        $v_root_count = 0;
        // $v_list = null;
        $v_current_pointer = null;
        $r = $this->m_reader;
        list($id, $v) = $r->tokenInfo();
        $start = $id == GraphQlReaderConstants::T_READ_START;
        $v_desc = null;
        // read field load 
        while (
            $start ||
            $r->read()
        ) {
            $start = false;
            list($id, $v, $e) = $r->tokenInfo();

            switch ($id) {

                case GraphQlReaderConstants::T_READ_SPEAR:
                    $name = $e[2];
                    // + | new property and name  
                    $this->_n_update_last_property($o, $v_property_info, $v_section_info);
                    // $v_property_info && ($o[$v_property_info->getKey()] = '---c');
                    $v_property_info = new GraphQlPropertyInfo($name); //  $this->_add_new_property($name, $v_desc, $v_property_alias, $v_section_info);
                    $v_property_info->type = GraphQlPropertyInfo::TYPE_SPEAR;
                    $v_property_info->alias = $v_alias;
                    $v_property_info->description = $v_desc;

                    //$sp = new GraphQlSpreadInfo($this, $o, $name, $v_property_info);
                    // + | remove section from object data
                    // unset($v_section_info->properties[$name]); 
                    // $o[] = $sp;
                    // $v_sp = array_key_last($o);
                    // $sp->key = $v_sp; 
                    // $v_section_info->properties[$name] = new GraphQlSpreadIndex($v_sp, $v_property_info);
                    // $v_fc_clear();

                    $this->_n_update_last_property($o, $v_property_info, $v_section_info, null, '...' . $name);

                    $v_property_info = null;
                    $v_alias = null;
                    $v_desc = null;
                    break;
                case GraphQlReaderConstants::T_READ_INLINE_SPEAR:
                    $v_property_info && $this->_n_update_last_property($o, $v_property_info, $v_section_info);
                    $this->_bind_inline_spear($r, $v_section_info, $v_desc, $o);
                    $v_desc = null;
                    $v_property_info = null;
                    break;


                case GraphQlReaderConstants::T_READ_DEFAULT_VALUE:
                    if ($v_property_info) {
                        if ($v_property_info->type !=  GraphQlPropertyInfo::TYPE_FUNC) {
                            $v_property_info->default = $v;
                            $v_property_info->optional = true;
                        } else {
                            throw new GraphQlException('invalid syntax');
                        }
                    } else {
                        throw new GraphQlException('missing property to set default');
                    }
                    break;

                case GraphQlReaderConstants::T_READ_DESCRIPTION:
                    $v_desc = $v;
                    break;
                case GraphQlReaderConstants::T_READ_DIRECTIVE:
                    if ($v_property_info) {
                        $v_property_info->directives[] = $v;
                    } else if ($v_section_info) {
                        $v_section_info->directives[] = $v;
                    } else {
                        throw new GraphQlSyntaxException('directive not allowed');
                    }
                    break;
                case GraphQlReaderConstants::T_READ_ALIAS:
                    if (!empty($v_alias)) {
                        throw new GraphQlSyntaxException("expected name got alias");
                    }
                    $v_alias = $v;
                    break;
                case GraphQlReaderConstants::T_READ_FUNC_ARGS:
                    if ($v_property_info) {
                        // replace with callback function if section is depending on 
                        // depend on global variable or depending on context view - require a listener to handle function 
                        $v_property_info->type = GraphQlPropertyInfo::TYPE_FUNC;
                        $v_property_info->args = $v;
                        $v_property_info->args_expression = $e[2];
                    }
                    break;
                case GraphQlReaderConstants::T_READ_NAME:
                    // attach property to section 
                    if ($v_brank > 0) {


                        $this->_n_update_last_property($o, $v_property_info, $v_section_info);



                        $v_property_info = new GraphQlPropertyInfo($v);
                        $v_property_info->alias = $v_alias;
                        $v_property_info->description = $v_desc;
                    }
                    $v_alias = null;
                    $v_desc = null;
                    break;
                case GraphQlReaderConstants::T_READ_START:
                    if (isset($v_o)) {
                        unset($v_o);
                    }
                    $v_no = [];
                    if ($v_brank == 0) {
                        $v_root_count++;
                        // init reading ... 
                        if ($v_root_count > 1) {
                            // store to v_list
                        }
                        $v_root = &$v_no;
                    } else {
                        // chain section - field
                        $v_property_info->hasChild = true;
                        $this->_n_update_last_property($o, $v_property_info, $v_section_info);
                    }
                    $v_section_info = $this->_n_chain_section($r, $v_section_info, $v_no, $v_current_pointer);
                    if ($v_property_info && $v_property_info->hasChild) {
                        $v_property_info->setChildSection($v_section_info);
                    }
                    $v_property_info = null;
                    $v_brank++;
                    $o = &$v_no;
                    unset($v_no);
                    break;
                case GraphQlReaderConstants::T_READ_END:
                    $this->_n_update_last_property($o, $v_property_info, $v_section_info);
                    if (!$v_section_info) {
                        throw new GraphQlSyntaxException("missing section");
                    }
                    if (empty($v_section_info->properties)) {
                        throw new GraphQlSyntaxException("missing property in section");
                    }

                    $v_brank--;
                    if ($v_brank == 0) {
                        // end branking 
                        return $o;
                    } else {
                        // + move pointer to parent 
                        $v_section_info = $v_section_info->parent;
                        $o = &$v_section_info->getRefPointer()->getRefData();
                    }
                    $v_property_info = null;
                    break;
            }
        }
        return false;
    }
}
