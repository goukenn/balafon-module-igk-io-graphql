<?php
// @author: C.A.D. BONDJE DOUE
// @file: InlineSpread.php
// @date: 20231013 08:32:10
namespace igk\io\GraphQl\Types;

use igk\io\GraphQl\GraphQlData;
use igk\io\GraphQl\GraphQlHooks;
use igk\io\GraphQl\GraphQlParser;
use igk\io\GraphQl\GraphQlReaderConstants;
use igk\io\GraphQl\GraphQlReadSectionInfo;
use igk\io\GraphQl\GraphQlSectionDefinitionReader;
use igk\io\GraphQl\GraphQlSyntaxException;
use igk\io\GraphQl\Helper\GraphQlReaderUtils;
use igk\io\GraphQl\IGraphQlDescribedProperty;
use igk\io\GraphQl\IGraphQlUpdateProperty;
use IGK\System\IO\StringBuilder;
use IGKEvents;

///<summary></summary>
/**
 * 
 * @package igk\io\GraphQl\Types
 */
class InlineSpread implements IGraphQlDescribedProperty, IGraphQlUpdateProperty
{
    private $m_on;
    private $m_definition;
    private $m_refObject;
    private $m_reader;
    private $m_section;
    private $m_key;

    //
    var $description;

    var $hasChild = true;

    /**
     * check on updated
     * @var mixed
     */
    private $m_update;

    public function getOn()
    {
        return $this->m_on;
    }
    public function getDefinition()
    {
        return $this->m_definition;
    }
    public function getSourceSection(){
        return $this->m_section;
    }
    /**
     * set graph section 
     * @param null|GraphQlReadSectionInfo $section 
     * @return void 
     */
    public function setSourceSection(?GraphQlReadSectionInfo $section){
        $this->m_section = $section;
    }

    protected function onType(){
        $listener = $this->m_reader->getListener(); 
        if ($listener && ($listener->getSourceTypeName() == $this->m_on)){
            return true;
        }
        return false; 
    }
    /**
     * update ref object
     * @param mixed $out 
     * @param mixed $source_data 
     * @param mixed $entry_data $top level entry data
     * @param mixed $section 
     * @return void 
     */
    public function UpdateRefObject(&$out , $source_data, $entry_data, $section=null){
        
        
        $v_out_data = [];
        $section = $section ?? $this->m_section;
        $v_source = $source_data;
        $key = $this->m_key;
        if (empty($this->m_update)){
            $this->m_update = 'manual';
        }
        if (!$this->onType()){
            unset($out[$key]); 
            return;
        }

        foreach ($this->m_definition->properties as $v) {
            $v->section = $section;
            $v_key = $v->getKey();
            if ($v->isReservedProperty()){
                $v_out_data[$v_key] = GraphQlReaderUtils::GetReservedValue($v);  
            } else { 
                $n_n = $v->name;
                // spread priority to entry source data
                if (!$v_source || !key_exists($n_n, $v_source))
                    if ($entry_data && key_exists($n_n, $entry_data)){
                        $v_source = $entry_data;
                    }


                $rdata = $v->getValue($v_source);
                if (($v->hasChild)&& ($rdata)){
                    $f_section = $v->getChildSection();
                    $f_section->parent->parent = $section; 
                    $cp = $f_section->getReferencedData($rdata, $entry_data); 
                    $f_section->parent->parent = null;
                    $rdata = is_array($cp) && empty($cp) ? null : $cp;
                }
                $v_out_data[$v_key] = $rdata;
            }
            $v->section = null;
        }
        GraphQlReaderUtils::ReplaceArrayDef($out, $key, $v_out_data);

        
    }
    public function __construct(GraphQlParser $reader, GraphQlReadSectionInfo $section, &$refObject)
    {
        $this->m_refObject = &$refObject;
        $this->m_reader = $reader;
        $section->properties[] = $this;
        $key = array_key_last($section->properties);
        $refObject[] = '__inline_spreed__';
        $key = array_key_last($refObject);
        $this->m_key = $key;
        $fe = function ($e) use ($key, $section) {
            
            list($v_section, $v_source, & $out) = $e->args;
            if ( $this->m_update || ($section !== $v_section)){
                return;
            }

            $this->m_update = 'byEntry';
            if (!$this->onType()){
                unset($out[$key]); 
            }
            else { 
                $this->UpdateRefObject($out, $data, $v_source); 
            }
            unset($out);
        };
        
        $ifes = function ($e) use ($reader, &$refObject, $key) {
            list($v_reader, $v_data) = $e->args;
            if ($v_reader !== $reader) {
                return;
            }
            if ($this->m_update)
                return; 
            if ($this->onType()) {
                //group resolution data 
                $v_out_data = [];
                if (is_null($v_data)) {
                    foreach ($this->m_definition->properties as $k => $v) {
                        $v_key = $v->getKey();
                        $v_out_data[$v_key] = null;
                    }
                    GraphQlReaderUtils::ReplaceArrayDef($refObject, $key, $v_out_data);
                } else {
                    $data = null;
                    if ($v_data instanceof GraphQlData) {
                        if ($v_data->isProvided()) {
                            if (!$v_data->isIndex()) {
                                $data = $v_data->first();

                                foreach ($this->m_definition->properties as $k => $v) {
                                    $v_key = $v->getKey();
                                    $v_out_data[$v_key] = $v->getValue($data);
                                }
                                GraphQlReaderUtils::ReplaceArrayDef($refObject, $key, $v_out_data);
                            }
                        }
                    }
                }
            }

            unset($refObject[$key]);
            $this->m_update = 'byEndQuery';
        };
        igk_reg_hook(GraphQlHooks::HookName(GraphQlHooks::HOOK_END_ENTRY), $fe); 
        igk_reg_hook(GraphQlHooks::HookName(GraphQlHooks::HOOK_END_QUERY), $ifes);


        IGKEvents::UnregComplete(
            GraphQlHooks::HookName(GraphQlHooks::HOOK_LOAD_COMPLETE),
            function ($e) use ($reader, &$fe, &$fes, &$ifes) {
                list($v_reader) = $e->args;
                if ($reader === $v_reader) {
                    igk_unreg_hook(GraphQlHooks::HookName(GraphQlHooks::HOOK_END_ENTRY), $fe); 
                    igk_unreg_hook(GraphQlHooks::HookName(GraphQlHooks::HOOK_END_QUERY), $ifes);
                    return true;
                }
                return false;
            }
        );
    }
    public function readDefinition(GraphQlParser $reader): bool
    {
        $v_brank = 0;
        $end = false;
        $v_on = false;
        $v_root_section = null;
        $v_section_info = null;
        $v_property_info = null;
        $v_field = [];
        $v_section_reader = new GraphQlSectionDefinitionReader($reader, $v_field, null);
        while ($reader->read()) {
            list($id, $v) = $reader->tokenInfo();
            GraphQlReaderUtils::CheckSectionBrank($id, $end, $v_brank);
            if ($end) break;

            switch ($id) {
                case GraphQlReaderConstants::T_READ_NAME:
                    if ($v_brank === 0) {
                        if (!$v_on) {
                            if ($v !== 'on') {
                                throw new GraphQlSyntaxException(sprintf('expected on but %s found'));
                            }
                            $v_on = true;
                        } else if ($v_on && empty($this->m_on)) {
                            $this->m_on = $v;
                        } else {
                            throw new GraphQlSyntaxException(sprintf('invalid syntax'));
                        }
                        continue 2;
                    }
                    break;
            }
            $v_section_reader->readerInfo($id, $v, $v_section_info, $v_property_info);

            if (is_null($v_root_section) && $v_section_info) {
                $v_root_section = $v_section_info;
            }
        }
        $this->m_definition = $v_root_section;

       // $r = $this->render();
        return true;
    }
    // passing data to section fields 
    public function bindSectionData($data)
    {
    }

    public function render()
    {
        $sb = new StringBuilder;
        $sb->append("... on " . $this->m_on . "{\n");
        $sb->append(GraphQlReaderUtils::RenderPropertiesDefinition($this->m_definition->properties));
        $sb->append("}");
        return $sb . '';
    }
}
