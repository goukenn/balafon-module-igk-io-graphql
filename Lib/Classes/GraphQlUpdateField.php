<?php
// @author: C.A.D. BONDJE DOUE
// @file: GraphQlUpdateField.php
// @date: 20231010 13:35:47
namespace igk\io\GraphQl;

use IGK\System\IO\Path;

///<summary></summary>
/**
* 
* @package igk\io\GraphQl
*/
class GraphQlUpdateField{
    private $m_reader;
    private $m_name;
    private $m_data;
    private $m_def;
    private $m_source;

    public function __construct($reader, GraphQlPropertyInfo $def, string $name, $data, & $o){
        $this->m_reader = $reader;
        $this->m_name = $name;
        $this->m_data = $data;
        $this->m_def = $def;
        $this->m_data = $data;
        $this->m_source = & $o;
        $HookKEY = GraphQlHooks::HookName(GraphQlHooks::HOOK_END_SECTION);

        $fc = function($e)use(& $fc, $HookKEY){
            list($reader, $section) = $e->args;
            if (($reader!==$this->m_reader) || ($section !== $this->m_def->section)){
                return;
            }
            $this->m_source[$this->m_name] = $this->update();
            igk_unreg_hook($HookKEY, $fc);
        };
        igk_reg_hook(GraphQlHooks::HookName(GraphQlHooks::HOOK_END_SECTION), $fc);
    } 
    /**
     * retrieve all value field
     * @return string[] 
     */
    public function update(){
        return ['surname'=>'presen'];
    }
}