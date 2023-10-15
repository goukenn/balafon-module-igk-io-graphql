<?php
// @author: C.A.D. BONDJE DOUE
// @file: GraphQlParser2.php
// @date: 20231009 16:35:22
namespace igk\io\GraphQl;

use IGK\Actions\Dispatcher;
use IGK\Helper\Activator;
use igk\io\GraphQl\GraphQlReaderConstants as rConst;
use igk\io\GraphQl\Helper\GraphQlReaderUtils;
use igk\io\GraphQl\Traits\GraphQlPropertyReadingTrait;
use igk\io\GraphQl\Traits\GraphQlReadCommentOptionsTrait;
use igk\io\GraphQl\Types\InlineSpread;
use igk\io\GraphQl\System\IO\GraphQlSectionReader; 
 
use IGKException;
use ReflectionMethod;
use stdClass;


// - task: return | skip query name for single query - expectation 

///<summary></summary>
/**
 * GraphQl query parser.
 * @package igk\io\GraphQl
 */
class GraphQlParser implements IGraphQlParserOptions
{
    const T_GRAPH_DECLARE_TYPE = 9;
    const T_GRAPH_DECLARE_INPUT = 10;

    use GraphQlPropertyReadingTrait;
    use GraphQlReadCommentOptionsTrait;

    /**
     * last loaded definition
     * @var mixed
     */
    private $m_definition;
    
    public function setListener(?IGraphQlInspector $listener){
        $this->m_listener = $listener;
        return $this;
    }
    /**
     * query string to read
     * @var mixed
     */
    private $m_query;
    /**
     * query variable to handle
     * @var mixed
     */
    private $m_variables;

    /**
     * the reading token
     * @var mixed
     */
    private $m_token;

    private $m_mapData;

    /**
     * offset of reading
     * @var int
     */
    private $m_offset = 0;

    /**
     * object use for listener value
     * @var IGraphQl
     */
    private $m_listener;

    /**
     * source data
     * @var mixed
     */
    private $m_data;

    /**
     * store length of the query
     * @var mixed
     */
    private $m_length;

    /**
     * store declared input information 
     * @var array
     */
    private $m_declaredInput = [];

    /**
     * store load input
     * @var mixed
     */
    protected $p_loadInput; 

    /**
     * get declared input 
     * @return ?array
     */
    public function getDeclaredInputs(): ?array
    {
        return $this->m_declaredInput;
    }
    /**
     * store declred input type
     * @param string $type 
     * @return void 
     */
    protected function storeDeclaredInput(string $type){
        if ($this->p_loadInput){
            $this->m_declaredInput[$type][] = $this->p_loadInput;
        }
    }
    /**
     * get fragments
     * @return mixed 
     * @throws IGKException 
     */
    public function getFragments()
    {
        return igk_getv($this->getDeclaredInputs(), 'fragment');
    }

    protected function __construct()
    {
    }
    /**
     * helper parse with options
     * @param array $options 
     * @param mixed $query_or_query_data 
     * @param mixed $data_or_listener 
     * @param mixed $reader 
     * @param null|igk\io\GraphQl\GraphqlMapData $mapData 
     * @return void 
     */
    public static function ParseWithOption(array $options, $query_or_query_data, $data_or_listener = null, &$reader = null, ?GraphqlMapData $mapData = null)
    {
        return static::Parse($query_or_query_data, $data_or_listener, $reader, $mapData, $options);
    }
    /**
     * get token info 
     * @return array 
     */
    public function tokenInfo(): array
    {
        $id = $v = $e = $this->token();
        if (is_array($e)) {
            $id = $e[0];
            $v = $e[1];
        }
        return [$id, $v, $e];
    }
    public function getDefinition(){
        return $this->m_definition;
    }
    /**
     * parse query and 
     * @param string|array $query in case of array. if array ['query'=>..., 'variables'=>...];
     * @param null|array|IGraphQlInspector $data_or_listener 
     * @param mixed $reader reader for debugging 
     * @return mixed|definition or null entry data return data 
     */
    public static function Parse($query_or_query_data, $data_or_listener = null, &$reader = null, 
    ?GraphqlMapData $mapData = null, ?array $initiator = null)
    {
        $query = $query_or_query_data;
        $variable = null;
        if (is_array($query)) {
            $tquery = $query;
            $variable = igk_getv($query, 'variables');
            $query = igk_getv($query, 'query') ?? igk_die('missing query');
            unset($tquery['variables']);
            unset($tquery['query']);
            if (!$initiator && (count($tquery) > 0)) {
                $initiator = $tquery;
            }
        }

        $reader =  new static;
        $reader->m_query = $query;
        $reader->m_variables = $variable;
        $reader->m_data = is_array($data_or_listener) ? GraphQlData::Create($data_or_listener) : new GraphQlData();
        $reader->m_listener = $data_or_listener instanceof IGraphQlInspector ? $data_or_listener : null;
        $reader->m_length = strlen($query);
        $reader->m_mapData = $mapData;

        if ($initiator) {
            Activator::BindProperties($reader, $initiator);
        }
        $o = $reader->_load();
        $reader->m_definition = $o;
        if ( $data_or_listener ){
            // passing data to chain list 
            return $reader->exec();
        }
       

        return $reader->viewDefault(); // GraphQlReaderUtils::InitDefaultProperties($o, null);

        // return $o;
    }
    public function viewDefault($default=null){
        $o = $this->m_definition;
        $p = GraphQlReaderUtils::InitDefaultProperties($o, $default);
        $tqueries = igk_getv($this->getDeclaredInputs(), 'query'); 
        if (count($tqueries)==1){
            if (!empty($tqueries[0]->name)){
                return [$tqueries[0]->name=>$p];
            }
        }
        return $p;
    }
    public function exec($mapping=null){
        $queries = igk_getv($this->getDeclaredInputs(), 'query');
        $mutation = igk_getv($this->getDeclaredInputs(), 'mutation');
        $ts = [];
        $v_model_mapping = $mapping ?? $this->_get_model_callback();
        $sourceType = $this->m_listener ? $this->m_listener->getSourceTypeName() : null;
        $noskip_entrie_ = 
        $this->noSkipFirstNamedQueryEntry;

        if ($queries){
            $root = true;
            $tms = $this->_get_root_data();
            while($queries){
                $q = array_shift($queries);
                $ms = $tms;
                $section = $q->getSection();
                $section->setSourceTypeName($sourceType);
                if (empty($queries) && $root){ 
                    $ns = $q->name;
                    $ms = $section->getData($ms, $v_model_mapping);
                    if (empty($ns)){
                        $ns='Query'; 
                        if (is_array($ms) && (count($ms)==1)){
                            if (!is_numeric($tns = array_key_first($ms))){
                                $ns = $tns;
                                $ms =  $ms[$ns]; // array_shift($ms); 
                            } 
                        }
                        // convert to definition 
                        // if (is_array($ms)&& igk_array_is_assoc($ms)){
                        //     $ms = (object)$ms;
                        // }
                    }  
                } else { 
                    $ns = $q->name;
                    if (empty($ns)){
                        $ns='Query'; 
                    } else {
                        $ms = igk_getv($ms, $ns, $tms);
                    }
                    $ms = $section->getData($ms, $v_model_mapping);
                }
                if (in_array($ns, ['Mutation'])){
                    throw new GraphQlSyntaxException('definition failed');
                }
                $ts[$ns] = is_array($ms) && empty($ms) ? null : $ms;
                $root =false;
            }
        }
        
        
        if ($mutation){
            // invoke mutations
            $exclude_name = ['Query'];
            $var = $this->m_variables;
            while (count($mutation)>0){
                // store mutation result
                $q = array_shift($mutation);
                $ns = $q->name ?? 'Mutation';
                $ms = null;
                in_array($ns, $exclude_name) && igk_die_exception(GraphQlSyntaxException::class, 'invalid mutation name. '.$ns);
                if (!$q->definition){
                    igk_die("missing definition");
                }
                // if ($q->argument){
                //     // by pass argument if not provided from root variables
                 
                // }  else 
                // {
                //     $this->m_variables = $var;
                // }
                $bs = [];
                foreach($q->definition as $k=>$v){
                    $v_key = $v->getKey() ?? $k;
                    $ms = null;
                    if ($v->type == GraphQlPropertyInfo::TYPE_FUNC)
                    {
                        $ms = GraphQlReaderUtils::InvokeListenerMethod($this, $v,null, $mapping, 'mutation');
                    }
                    if ($ms){ 
                        $bs[$v_key] = $ms;
                    }
                }
                
                if (($ns=='Mutation') || $q->argument){
                    if (count($bs) == 1){
                        if (!is_numeric($nk = array_key_first($bs))){
                            $ns = $nk;
                            $bs = $bs[$nk];
                        }
                    }
                }
                
                $ts[$ns] = $bs;
            }
            $this->m_variables =  $var;
        }

        // use in order for fragment to load property data 
        igk_hook(
            GraphQlHooks::HookName(GraphQlHooks::HOOK_LOAD_COMPLETE),
            [$this]
        );  
        return $ts;
    }
    /**
     * load and parse data 
     * @return null|array|object 
     */
    protected function _load()
    {
        // 20231014 - new load implementation
        $v_root = null;
        $v_o = null;
        $v_brank = 0;
        $v_alias = null;
        $v_root_count = 0;
        $v_list = null;
        $v_desc = null;
        $v_flag_named_query = false;
        $v_def_name = false;
        $v_last_entry_name = null;

        // read field fload 
        while ($this->read()) {
            list($id, $v) = $this->tokenInfo();
            switch ($id) {
                case GraphQlReaderConstants::T_READ_COMMENT:
                    $this->_bind_comment($v);
                    break;
                case GraphQlReaderConstants::T_READ_DESCRIPTION:
                    $v_desc = $v;
                    break;
                case GraphQlReaderConstants::T_READ_ALIAS:
                    if (!empty($v_alias)) {
                        throw new GraphQlSyntaxException("expected name got alias");
                    }
                    $v_alias = $v;
                    break;
                case GraphQlReaderConstants::T_READ_FUNC_ARGS:
                    if ($this->p_loadInput) {
                        $this->p_loadInput->parameters = $v;
                    }
                    break;
                case GraphQlReaderConstants::T_READ_NAME:
                    // attach property to section 
                    if ($v_brank == 0) {
                        //binding query desc 
                        $v_last_entry_name = null;
                        if ($v_def_name) {
                            if (($this->p_loadInput->type =='query') && (strtolower($v) == 'query')){
                                $v =  null;
                            }
                            $this->p_loadInput->name = $v;
                            $v_def_name = false;
                        } else {
                            // top brank reading definition property 
                            $this->_bind_delcaredInput($v, $v_def_name, $v_desc);
                            if (!$v_def_name) {
                                $v_last_entry_name = $v;
                            }
                        }
                    }
                    $v_alias = null;
                    break;
                case GraphQlReaderConstants::T_READ_START:
                    if (isset($v_o)) {
                        unset($v_o);
                    }
                    $sector = new GraphQlSectionReader($this, $v_desc);
                    $sector->copyState($this);
              
                
                    if (($v_o = $sector->read()) !== false) {
                        $v_tabquery = igk_getv($this->getDeclaredInputs(), 'query');
                        $v_tquery = null;

                       
                        if (is_null($this->p_loadInput)) { 
                            $v_tabquery && $v_tquery = igk_array_find_first($v_tabquery, function($a){
                                if (empty($a->name)) {
                                    return $a;
                                }
                            });
                            if (!$v_tquery){ 
                                $this->p_loadInput = GraphQlDeclaredInputFactory::Create('query');
                                $this->storeDeclaredInput('query');
                                if ($v_last_entry_name != 'Query'){
                                    $this->p_loadInput->name = $v_last_entry_name;
                                }
                               
                            }
                        } else {
                            $v_tabquery && $v_tquery = igk_array_find_first($v_tabquery, function($a){
                                if ($a->name ==  $this->p_loadInput->name) {
                                    return $a;
                                }
                            });
                            if ($v_tquery && empty($v_tquery->definition)){
                                // reset query to avoid 
                                $v_tquery = null;
                            }
                        }
                        if ($v_tquery){
                            // + | --------------------------------------------------------------------
                            // + | MERGE ROOT PROPERTIEs
                            // + |
                            
                            // + | MERGE PROPERTIES - 
                            // -----------------------------------------------------------
                            $index = $this->p_loadInput ? 
                                array_search($this->p_loadInput, $v_tabquery) : false;
                            if ($index !== false){
                                unset($v_tabquery[$index]);
                                $this->m_declaredInput['query'] = $v_tabquery;
                            }

                            $this->p_loadInput = $v_tquery;
                            $v_n = array_key_first($v_tquery->definition);
                            $m = $v_tquery->definition[$v_n]->section;
                            foreach($v_o as $k){
                                $k->section = $m;
                                $m->properties[$k->getKey()] = $k;
                            }
                            $v_o = array_merge($v_tquery->definition, $v_o);
                        }
                        $this->p_loadInput->definition = &$v_o;
                        if ($v_root && !$v_tquery) {
                            if (is_null($v_list)) {
                                $v_list = [$v_root];
                            }
                            $v_list[] = &$v_o;
                        }
                        $v_root = &$v_o;
                        $this->p_loadInput = null;
                    } else {
                        return false;
                    }
                    $v_root_count++;
                    $v_last_entry_name  =null;
                    break;
            }
        }
      
        if ($v_list) {
            if (!$this->noSkipFirstNamedQueryEntry && $v_flag_named_query && (count($v_list) == 1)) {
                // + | skip query name for single properties
                $v_first_key = array_key_first($v_list);
                return (object)$v_list[$v_first_key];
            }
            return $v_list;
        }
        return $v_root;

        // /**
        //  * @var ?GraphQlReadSectionInfo $v_section_info 
        //  */
        // $o = null;
        // $v_root= null;
        // $v_brank = 0;
        // /**
        //  * @var ?GraphQlReadSectionInfo $v_section_info 
        //  */
        // $v_section_info = null;     // <- section reading info
        // $v_desc = null;             // <- preload reading description 
        // $v_property_name = null;    // <- store the current property name to load
        // $v_property_alias = null; // <- store property alias
        // $p = []; // container 
        // $v_chain_args = new GraphQlChainGraph;
        // $v_chainstart = false;
        // $v_property_info = null;  // <- store property info
        // $data = $this->_get_root_data(); // <- init root data
        // $v_list = null;
        // $v_fc_clear = function () use (&$v_property_info, &$v_property_name, &$v_property_alias) {
        //     $v_property_info = null;
        //     $v_property_name = null;
        //     $v_property_alias = null;
        // };
        // $v_model_mapping = $this->_get_model_callback();
        // $v_out_data = null;
        // $v_def_name = false;
        // $v_current_data_def = null;
        // $v_key =
        //     $v_p = null;

        // $v_data_is_indexed = GraphQlData::IsIndexed($data);
        // $v_load_indexed = false;
        // $v_index_list = false;
        // $v_flag_named_query = false;
        // $options_defs = null;
        // $v_last_entry_name = null;  // <- get entry name
 
        // $v_root_counter = 0; // <!-- counter maximun 

        // $loup_ = null;

        // while ($this->read()) {
        //     $id = $v = $e = $this->token();
        //     if (is_array($e)) {
        //         $id = $e[0];
        //         $v = $e[1];
        //     }
        //     $this->_update_chain_args($v_chain_args, $id, $v, $v_chainstart);
 
        //     switch ($id) {
        //         case rConst::T_READ_FUNC_ARGS:
        //             if ($v_property_info) {
        //                 // replace with callback function if section is depending on 
        //                 // depend on global variable or depending on context view - require a listener to handle function 
        //                 if ($this->m_listener) {
        //                     $args = $v;

        //                     $v_property_info->type = GraphQlPropertyInfo::TYPE_FUNC;
        //                     $v_property_info->args = $args;
        //                     $v_key = $v_property_info->getKey();
                            
        //                     $o[$v_key] = $v_property_info; // new GraphQlReferenceSectionProperty($this, $o, $v_key, $v_section_info, $v_property_info);
                            
        //                 }
        //             }
        //             break;
        //         case rConst::T_READ_COMMENT:
        //             $this->_bind_comment($v); 
        //             break;
        //         case rConst::T_READ_DEFAULT_VALUE:
        //             if ($v_property_info){
        //                 if ($v_property_info->type !=  GraphQlPropertyInfo::TYPE_FUNC){
        //                     $v_property_info->default = $v;
        //                     $v_property_info->optional = true;
        //                 } else {
        //                     throw new GraphQlException('invalid syntax');
        //                 }
        //             } else {
        //                 throw new GraphQlException('missing property to set default');
        //             }
        //             break;
        //         case rConst::T_READ_DESCRIPTION:
        //             $v_desc = $v;
        //             break;
        //         case rConst::T_READ_DIRECTIVE:
        //             if ($v_property_info) {
        //                 $v_property_info->directives[] = $v;
        //             } else if ($v_section_info) {
        //                 $v_section_info->directives[] = $v;
        //             } else {
        //                 throw new GraphQlSyntaxException('directive not allowed');
        //             }
        //             break;
        //         case rConst::T_READ_SPEAR:
        //             $name = $e[2];
        //             // + | new property and name  
        //             $this->_update_last_property($o, $v_property_info, $data);
        //             // $v_property_info && ($o[$v_property_info->getKey()] = '---c');
        //             $v_property_info = $this->_add_new_property($name, $v_desc, $v_property_alias, $v_section_info);
        //             $v_property_info->type = GraphQlPropertyInfo::TYPE_SPEAR;
        //             $sp = new GraphQlSpreadInfo($this, $o, $name, $v_property_info);
        //             // + | remove section from object data
        //             unset($v_section_info->properties[$name]); 
        //             $o[] = $sp;
        //             $v_sp = array_key_last($o);
        //             $sp->key = $v_sp;
        //             $v_property_name = null; 
        //             $v_section_info->properties[$name] = new GraphQlSpreadIndex($v_sp, $v_property_info);
        //             $v_fc_clear();
        //             break;
        //         case rConst::T_READ_INLINE_SPEAR:
        //             $v_property_info && $this->_update_last_property($o, $v_property_info, $data);
        //             $this->_bind_inline_spear($v_section_info, $v_desc, $o); 
        //             $v_desc = null;
        //             $v_property_info = null;
        //             break;
        //         case rConst::T_READ_NAME:
        //             if ($v_brank == 0) {
        //                 $v_last_entry_name = null;
        //                 if ($v_def_name) {
        //                     $this->p_loadInput->name = $v;
        //                     $v_def_name = false;
        //                 } else {
        //                     // top brank reading definition property 
        //                     $this->_bind_delcaredInput($v, $v_def_name, $v_desc);
        //                     if (!$v_def_name) {
        //                         $v_last_entry_name = $v;
        //                     }
        //                 }
        //             } else {
        //                 if (!$v_section_info) {
        //                     throw new GraphQlSyntaxException('read name but no section found');
        //                 }

        //                 $this->_update_last_property($o, $v_property_info, $data);
        //                 $v_property_info = $this->_add_new_property($v, $v_desc, $v_property_alias, $v_section_info);
        //                 $v_property_alias = null;
        //                 $v_desc = null;
        //                 $v_property_name = $v;
        //             }
        //             break;
        //         case rConst::T_READ_ALIAS:
        //             // read field alias
        //             if (!empty($v_property_alias)) {
        //                 throw new GraphQlSyntaxException('already set alias');
        //             }
        //             $v_property_alias = $v;
        //             break;
        //         case rConst::T_READ_START:
        //             $v_bind_section = false;
        //             if ($v_brank == 0) {
        //                 $v_root_counter ++;
                        
        //                 if (!is_null($o)) {
        //                     if (is_null($v_list)) {
        //                         $v_list = [];
        //                         $v_list[] = $o;
        //                         unset($o);
        //                         $o = null;
        //                     }
        //                     $o = [];
        //                     $v_list[] = $o;
        //                     $o = &$v_list[array_key_last($v_list)];
        //                 } else {
        //                     // + | start object creation
        //                     $o = [];
        //                     $v_root = & $o;
        //                 }
        //                 $v_current_data_def = new GraphQlPointerObject($o);
        //             } else {
        //                 // + | sub properties
        //                 $v_key = $v_property_info->getKey();
        //                 $this->_init_child_property($o, $v_key, $v_property_info, $data);
        //                 // $v_section_info->name = $v_property_name;
        //                 // $v_section_info->alias = $v_property_alias; 
        //                 $v_section_info = $this->_chain_info($v_section_info);
                        
        //                 $v_ref = new GraphQlReferenceSectionProperty($this, $o, $v_key, $v_section_info, $v_property_info);
        //                 $v_new_o = [];
        //                 $v_current_data_def = new GraphQlPointerObject($v_new_o, $v_current_data_def);
        //                 $o[$v_key] = $v_current_data_def;
        //                 unset($o);
        //                 $o = null;
        //                 $o = &$v_new_o;
        //                 $v_bind_section = true;
        //                 if ($v_property_info->type == GraphQlPropertyInfo::TYPE_FUNC) {
        //                     $v_section_info->type = $v_property_info->type;
        //                 }
        //             }
        //             $v_brank++;
        //             // start 
        //             if (!$v_bind_section) {
        //                 $v_section_info = $this->_chain_info($v_section_info);
        //             }
        //             $v_fc_clear();
        //             break;

        //         // + | --------------------------------------------------------------------
        //         // + | DO END READ 
        //         // + |
                    
        //         case rConst::T_READ_END:
        //             if (!$v_section_info) {
        //                 throw new GraphQlSyntaxException("missing section");
        //             }
        //             if (empty($v_section_info->properties)) {
        //                 throw new GraphQlSyntaxException("missing property in section");
        //             }
        //             $this->_update_last_property($o, $v_property_info, $data);

        //            //  igk_debug_wln('end after _render ');


        //             if ($v_section_info->type == GraphQlPropertyInfo::TYPE_FUNC) {
        //                 // invoke function and 
        //                 igk_hook(GraphQlHooks::HookName(GraphQlHooks::HOOK_SECTION_FUNC), [$this, $v_section_info, $data]);
        //             }
        //             // else {
        //             //     if (($v_data_is_indexed) && empty($o)) {
        //             //         $tb = $data->entry;
        //             //         $o = $v_section_info->getData($tb, $v_model_mapping);
        //             //         $v_load_indexed = true;
        //             //     }
        //             // }

        //             // + | update indexed array
        //             // if ($data instanceof GraphQlData) {
        //             //     if (!$data->isIndex()) {

        //             //         igk_hook(
        //             //             GraphQlHooks::HookName(GraphQlHooks::HOOK_END_ENTRY),
        //             //             [$v_section_info, $data->first(), &$o]
        //             //         );
        //             //     }
        //             // }
        //             // + | send signal to close current section 
        //             $this->_endLoading($v_section_info, $data, $o);

        //             $v_brank--;
        //             if ($v_brank == 0) {
        //                 // + | send signal to end a section query
        //                 $v_free_o =false;
        //                 if ($this->p_loadInput) {
        //                     if (is_null($v_list)) {
        //                         $v_list = [$this->p_loadInput->name => & $o];
        //                     } else {
        //                         $v_list[$this->p_loadInput->name] = & $o;
        //                     }
        //                     $v_flag_named_query = true;
        //                     // + | update input query definition
        //                     foreach($v_section_info->properties as $k=>$v){
        //                         $this->p_loadInput->definition[$k] = ['name'=>$k];
        //                     }
        //                     $v_free_o  = true; 
        //                 } else if ($v_load_indexed && is_array($o)) {
        //                     if (!$v_index_list) {
        //                         $v_list = $o;
        //                         $v_index_list = true;
        //                     } else {
        //                         $v_list[] = $o;
        //                     }
        //                 }

        //                 $this->_endQuerySection($v_section_info, $v_data_is_indexed, $data, $o, $v_model_mapping); 
                        
        //                 if ($v_free_o){ 
        //                     unset($o);
        //                     $o = null;
        //                 }
        //                 // + | <- invoke end query 
        //                 igk_hook(GraphQlHooks::HookName(GraphQlHooks::HOOK_END_QUERY), [$this, $data]);
        //                 $v_section_info = null;

        //             } else {
        //                 if ($v_section_info instanceof GraphQlReadSectionInfo) {
        //                     $v_section_info = $v_section_info->parent;
        //                 }
        //             }
        //             if ($v_p = $v_current_data_def->getParentRef()) {
        //                 $o = &$v_p->getRefData();
        //                 $v_current_data_def = $v_p;
        //             } 
        //             $v_fc_clear();
        //             break;
        //     }
        // }
        // if ($o || $v_list) {
        //     igk_hook(Path::Combine(GraphQlHooks::class, GraphQlHooks::HOOK_LOAD_COMPLETE), [$this, $data]);
        // }
        // // | <- list of object 
        // if (!is_null($v_list)) {
        //     unset($o);
        //     if (!$this->noSkipFirstNamedQueryEntry && $v_flag_named_query && (count($v_list) == 1)) {
        //         // + | skip query name for single properties
        //         $v_first_key = array_key_first($v_list);
        //         return (object)$v_list[$v_first_key];
        //     }
        //     return $v_list;
        // }
        // if (!empty($v_last_entry_name)) {
        //     if ($this->noSkipFirstNamedQueryEntry) {
        //         return [$v_last_entry_name => $o];
        //     }
        // }
        // if (($v_data_is_indexed)&&  ($v_root_counter==1) && is_array($o)){
        //     return $o;
        // }
        // return (object)$o;
    }

    /**
     * raise end query section 
     * @param GraphQlReadSectionInfo $section 
     * @param bool $v_data_is_indexed 
     * @param mixed $data 
     * @param mixed $o 
     * @param mixed $mapping 
     * @return void 
     * @throws IGKException 
     */
    protected function _endQuerySection(GraphQlReadSectionInfo $section, bool $v_data_is_indexed, $data, & $o, $mapping){
        if ($data instanceof GraphQlData){
            $data = $data->entry;
        }
        if ($v_data_is_indexed){
            // + | source data is indexed 
            $tout = [];
            $tout = $section->getData($data, $mapping, $o); 
            $o = $tout; 
        } else {
            // + | for non indexed data 
            if ($data){

                $tout = $section->getData($data, $mapping, $o);
                $o = $tout;   
            }
            else {
                $tout = $section->getData(null, $mapping, $o);
                $o = $tout;   
            }
        }
    }
    protected function _bind_inline_spear(GraphQlReadSectionInfo $section_info, ?string $description, &$refObject){
        $v_inf = new InlineSpread($this, $section_info, $refObject);
        $v_inf->description = $description;
        $v_inf->readDefinition($this);
    }
    protected function _bind_comment(string $v)
    {
        GraphQlReaderUtils::BindComment($this, $v);
       
    }
    protected function _add_new_property(string $name, ?string $desc, ?string $alias, GraphQlReadSectionInfo $section)
    {
        $v_property_info = new GraphQlPropertyInfo($name);
        $v_property_info->alias = $alias;
        $v_property_info->section = $section;
        $v_property_info->description = $desc;
        $section->properties[$name] = $v_property_info;
        return $v_property_info;
    }

    /**
     * init child property 
     * @param mixed $o 
     * @param string $v_key 
     * @param GraphQlPropertyInfo $v_property_info 
     * @param mixed $data 
     * @return void 
     */
    protected function _init_child_property(&$o, string $v_key, GraphQlPropertyInfo $v_property_info, $data)
    {
        $v_property_info->hasChild = true;
        $o[$v_key] = null;
    }

    /**
     * update field property 
     * @param mixed $o 
     * @param GraphQlPropertyInfo $v_property_info 
     * @param mixed $data 
     * @return void 
     * @throws IGKException 
     */
    protected function _update_single_property(&$o, GraphQlPropertyInfo $v_property_info, $data)
    {

        if ($v_property_info->hasChild) {
            return;
        }
        if ($data instanceof GraphQlData) {
            if (!$data->isIndex()) {
                $data = $data->first();
            }
        }

        if (!$this->noThrowOnMissingProperty && !igk_key_exists($data, $v_property_info->name)) {
            throw new GraphQlSyntaxException(sprintf('missing property [%s]', $v_property_info->name));
        }
        $v_key = $v_property_info->getKey();
        $o[$v_key] = $this->_get_property_value($data, $v_property_info);
    }
    protected function _update_last_property(&$o, ?GraphQlPropertyInfo $v_property_info, $data=null)
    {
        $this->_updateLastProperty($o, $v_property_info, $data);
       
    }
    protected function _get_property_value($data, $v_property_info)
    {
        return igk_getv($data, $v_property_info->name);
    }

    protected function _get_model_callback()
    {
        return function ($tn) {
            return $this->getMapData($tn);
        };
    }
    /**
     * 
     * @param mixed $o data to update
     * @param GraphQlReadSectionInfo $section 
     * @param mixed $data source data
     * @return void 
     * @throws IGKException 
     */
    protected function _update_properties(&$o, GraphQlReadSectionInfo $section, $data)
    {

        foreach ($section->properties as $k => $def) {
            if ($def->section && $def->section->name) {
                // $obj = igk_conf_get($data, 'lastname/surname');
                $o[$def->section->name] = new GraphQlUpdateField($this, $def, $def->section->name, $data, $o);
                continue;
            }
            $v_k = $def->alias ?? $k;
            if (!key_exists($v_k, $o)) {
                $o[$v_k] = igk_getv($data, $def->name); //$this->_get_value($def, $v_chain_args);
            }
        }
    }
    /**
     * get the current listener 
     * @return ?IGraphQlInspector 
     */
    public function getListener(){
        return $this->m_listener;
    }
    /**
     * resolv map data - priority to listener 
     * @param string $typeName 
     * @return mixed 
     * @throws IGKException 
     */
    public function getMapData(string $typeName)
    {
        if ($this->m_listener instanceof IGraphQlMapDataResolver)
            return $this->m_listener->getMapData($typeName);
        return igk_getv($this->m_mapData, $typeName);
    }
    protected function _update_chain_args(GraphQlChainGraph $chain_args, $id, $v, &$chain_start)
    {
        $chain_args->update($id, $v, $chain_start);
    }
    protected function _get_value($definition, $v_chain_args)
    {
        $data = null;
        if (!$this->m_data->isProvided()) {
            if ($this->m_listener) {
                $data = $this->m_listener->query();
            }
        } else {
            $data = $this->m_data->first();
        }
        if ($data) {
            return igk_getv($data, $definition->name);
        }
        return null;
    }
    protected function _check_declared_token(string $name)
    {
        return igk_getv([
            "type" => self::T_GRAPH_DECLARE_TYPE,
            "input" => self::T_GRAPH_DECLARE_INPUT,
            "query" => self::T_GRAPH_DECLARE_INPUT,
            "mutation" => self::T_GRAPH_DECLARE_INPUT,
            "on" => self::T_GRAPH_DECLARE_INPUT,
            "extends" => self::T_GRAPH_DECLARE_INPUT,
            "implements" => self::T_GRAPH_DECLARE_INPUT,
            "fragment" => self::T_GRAPH_DECLARE_INPUT,
            "enum" => self::T_GRAPH_DECLARE_INPUT,
            "subscription" => self::T_GRAPH_DECLARE_INPUT,
        ], $name);
    }

    protected function _bind_delcaredInput(string $name, &$def_name, ?string $description = null)
    {
        $v_declaredInputs = &$this->m_declaredInput;
        $v_ldinput = &$this->p_loadInput;
        $def_name = false;
        $tn = $this->_check_declared_token($name);
        $v_declaredTypes = null;
     
        $v = $name;
        // create definition - 
        if ($tn === self::T_GRAPH_DECLARE_INPUT) {
            // $v_pinput = $v_ldinput;
            $v_ldinput = GraphQlDeclaredInputFactory::Create($v)  ?? igk_die('missing declared input name [' . $v . ']');

            $v_ldinput->description = $description;
            if (!isset($v_declaredInputs[$v])) {
                $v_declaredInputs[$v] = [];
            }


            $v_declaredInputs[$v][] = $v_ldinput;
            if ($v_ldinput->readDefinition($this)) {
                $v_ldinput = $v_ldinput->parent;
            } else {
                // query request
                $def_name = true;
            }
        }
        if ($tn === self::T_GRAPH_DECLARE_TYPE) {
            // $v_pinput = $v_ldinput;
            if (!isset($v_declaredInputs['type'])) {
                $v_declaredInputs['type'] = [];
            }
            $v_declaredTypes =  &$v_declaredInputs['type'];
            $v_type_name = '';
            if ($this->read()) {
                $e = $this->m_token;
                if ($e[0] == rConst::T_READ_NAME) {
                    $v_type_name = $e[1];
                }
            } else {
                throw new GraphQlSyntaxException('missing type name');
            }
            $v_ldinput = new GraphQlDeclaredType();
            $v_ldinput->name = $v_type_name;
            $v_ldinput->description = $description;
            $v_declaredTypes[$v_type_name] = $v_ldinput;

            $v_ldinput->readDefinition($this);
            //$v_declaredInputs['type'][] = $v_declaredTypes;
        }
    }
    /**
     * init and chain section 
     * @param null|GraphQlReadSectionInfo $v_section 
     * @return GraphQlReadSectionInfo 
     */
    // protected function _chain_info(?GraphQlReadSectionInfo $v_section): GraphQlReadSectionInfo
    // {
    //     $v_parent = $v_section;
    //     $v_section = new GraphQlReadSectionInfo($this, );
    //     $v_section->parent = $v_parent; 
    //     return $v_section;
    // }

    protected function _endLoading(GraphQlReadSectionInfo $info, $data, $sourceMap=null)
    {
        // + | query end - 
        if (($data instanceof GraphQlData)&& $data->isProvided() ) {
            if ($info->parent && !$data->isIndex()) {
                $path = $info->getFullPath();
                $data = igk_conf_get($data->entry, $path);
            }
        }
        igk_hook( GraphQlHooks::HookName( GraphQlHooks::HOOK_END_SECTION), 
            [$this, $info, $data, $sourceMap]);
    }

    /**
     * get the stored token info
     * @return string|array
     */
    public function token()
    {
        return $this->m_token;
    }

    protected function _is_alias(&$offset): bool
    {
        $v_alias = false;
        while ($offset < $this->m_length) {
            $ch = $this->m_query[$offset];
            if ($ch == ':') {
                $v_alias = true;
                break;
            } else if (trim($ch) == '') {
                $offset++;
            } else
                break;
        }
        return $v_alias;
    }
    /**
     * read and progress
     * @return bool 
     */
    public function read(): bool
    {
        $offset = &$this->m_offset;
        $v_skip_space = false;
        $v_query = $this->m_query;
        $v = '';
        $ln_field = false;
        while ($offset < $this->m_length) {
            $ch = $this->m_query[$offset];
            if ($ln_field) {
                if ($ch != "\n") {
                    return $this->_gen_token([rConst::T_READ_END_FIELD, '\n']);
                }
            }
            $v_in_token = strpos(rConst::T_TOKEN, $ch)!== false;
            if (!$v_in_token) {
                if (!empty($v)) {
                    // check for alias
                    $alias = $this->_is_alias($offset);
                    return $this->_gen_token([$alias ? rConst::T_READ_ALIAS : rConst::T_READ_NAME, $v]);
                }
            }
            $offset++;
            switch ($ch) {
                case '.':
                    // + | check for fragment spear 
                    // + | syntax 1 : ...spear_name
                    // + | syntax 2 (inline spear ): ... on TYPE{ field_n+ }
                    if (($v_spead = $ch . substr($this->m_query, $offset, 2)) == rConst::SPEAR_OPERATOR) {
                        $v_d = [rConst::T_READ_SPEAR, $v_spead];
                        $offset += 2;
                        $v_name = $this->_read_name($offset);
                        if (empty($v_name)) {
                            // detect next 'on'
                            if (($rp = strpos($this->m_query, 'on ',$offset))!==false){
                                if (trim(substr($this->m_query, $offset, ($rp+3) - $offset)) == 'on'){
                                    return $this->_gen_token([rConst::T_READ_INLINE_SPEAR, '']);
                                }

                            }
                            throw new GraphQlSyntaxException("spear operation missing name");
                        }
                        $v_d[] = $v_name;
                        $this->m_token = $v_d;
                        return true;
                    }
                    break;
                case '(': // read argument definition
                    return $this->_read_func_args();
                case ')':
                    return $this->_gen_token([rConst::T_READ_END_FUNC_ARGS, $ch]);
                case '#': // read line comment
                    $v = $ch . $this->_read_comment();
                    return $this->_gen_token([rConst::T_READ_COMMENT, $v]);
                case '{':
                    return $this->_gen_token([rConst::T_READ_START, $ch]);
                case '}':
                    return $this->_gen_token([rConst::T_READ_END, $ch]);
                case '@':
                    return $this->_read_directive();
                case '"':
                    $p = $ch . substr($v_query, $offset, 2);
                    $desc = '';
                    if ($p == '"""') {
                        // read multi line description 
                        $offset += 3;
                        $desc = $this->_read_multiline_doc();
                    } else {
                        $d = trim(igk_str_remove_quote($ch . igk_str_read_brank($v_query, $offset, $ch, $ch)));
                        $desc = $d; //$this->_read_single_line_doc();
                        $offset++;
                    }
                    return $this->_gen_token([rConst::T_READ_DESCRIPTION, $desc]);

                case '=':
                    // by default read default value 
                    if (!empty($v)) {
                        return $this->_gen_token([rConst::T_READ_NAME, $v]);
                    }

                    return $this->_read_default_value();

                    break;
                case ' ':
                    if (!$v_skip_space) {
                        $v_skip_space = true;
                    }
                    break;
                case "\n":
                case ",":
                    // if , split end - block
                    if ($ch == "\n") {
                        $ln_field = true;
                    } else {
                        return $this->_gen_token([rConst::T_READ_END_FIELD, $ch]);
                    }

                    break;
                default:
                    if ($v_in_token !== false) {
                        $v .= $ch;
                    } else {
                        if (!empty($v)) {
                            return $this->_gen_token([rConst::T_READ_NAME, $v]);
                        }
                    }
                    break;
            }
        }
        return false;
    }
    protected function _gen_token($v)
    {
        $this->m_token = $v;
        return true;
    }
    protected function _read_default_value(): bool{
        $v = '';


        $offset = &$this->m_offset;
        $end = false;
        $r = false;
        while (!$end && ($offset < $this->m_length)) {
            
            $ch = $this->m_query[$offset];
            $offset++;
            if (!$r && empty(trim($ch))){
                if (!is_numeric($ch)){
                    continue;
                }
            }
          
            if (($ch=="\"")||($ch=="'")){
                $v = igk_str_remove_quote($ch.igk_str_read_brank($this->m_query, $offset,$ch,$ch));
                $offset++;
                break;
            }
            if ($r && empty(trim($ch))){
                break;
            }
            $v .= $ch;
            $r = true;

        }

        if (is_numeric($v)){
            $v = floatval($v);
        } else if (in_array($v, ['null', 'nil'])){
            $v = null;
        }
        return $this->_gen_token([rConst::T_READ_DEFAULT_VALUE, $v]);
    }
    protected function _read_directive(): bool
    {
        $n = $this->_read_name();
        $d = new GraphQlDirectiveInfo;
        $d->name = $n;
        $offset = &$this->m_offset;
        $end = false;
        while (!$end && ($offset < $this->m_length)) {
            $ch = $this->m_query[$offset];
            $offset++;
            if (empty(trim($ch))) {
                continue;
            }
            if ($ch == "(") {
                if ($this->_read_func_args()) {
                    $e = $this->token();
                    if ($e[0] == rConst::T_READ_FUNC_ARGS) {
                        $d->args = $e[1];
                        $d->args_expression = $e[2];
                    }
                }
                $end = true;
            } else {
                // + | directive missing argument
                $offset--;
                break;
            }
        }

        return $this->_gen_token([rConst::T_READ_DIRECTIVE, $d]);
    }
    /**
     * read name 
     * @return string 
     */
    protected function _read_name()
    {
        $end = false;
        $offset = &$this->m_offset;
        $s = '';
        while (!$end && ($offset < $this->m_length)) {
            $ch = $this->m_query[$offset];
            if (strpos(rConst::T_TOKEN, $ch) === false) {
                $end = true;
                continue;
            }
            $s .= $ch;
            $offset++;
        }
        return $s;
    }
    protected function _read_variables()
    {
        // read variable and remplcate car 
    }
    protected function _read_func_args()
    {
        $args = null;
        $s = '';
        $end = false;
        $offset = &$this->m_offset;
        while (!$end && ($offset < $this->m_length)) {
            $ch = $this->m_query[$offset];
            if ($ch == ")") {
                $end = true;
                continue;
            }
            switch ($ch) {
                case '"':
                    $ch = $ch . igk_str_read_brank($this->m_query, $offset, $ch, $ch);
                    break;
            }
            $s .= $ch;
            $offset++;
        }
        $args = !empty($s) ? $this->_export_arg_definition($s) : null;
        // parse func args 
        return $this->_gen_token([rConst::T_READ_FUNC_ARGS, $args, $s]);
    }
    /**
     * export argument 
     * @param string $data 
     * @return false|stdClass 
     */
    protected function _export_arg_definition(string $data)
    {
        $tvar = []; 
        $tvar = GraphQlReaderUtils::MergeVariableToExport($this->m_variables, 
        $this->p_loadInput->argument);

        $v_export = new GraphQlExportArgument;
        $v_export->variables = $tvar;
        $o = $v_export->export($data);
        return $o;
    }
    /**
     * read single line-comment 
     * @return mixed 
     */
    protected function _read_comment()
    {
        $s = $this->m_query;
        $pos = strpos($s, "\n", $this->m_offset);
        if ($pos === false) {
            $v = substr($s, $this->m_offset);
            $this->m_offset = $this->m_length;
        } else {
            $v = trim(substr($s, $this->m_offset, $pos - $this->m_offset));
            $this->m_offset = $pos + 1;
        }
        return trim($v);
    }
    protected function _read_multiline_doc()
    {
        $s = $this->m_query;
        $pos = strpos($s, '"""', $this->m_offset);
        if ($pos === false) {
            $v = substr($s, $this->m_offset);
            $this->m_offset = $this->m_length;
        } else {
            $v = trim(substr($s, $this->m_offset, $pos - $this->m_offset));
            $this->m_offset = $pos + 3;
        }
        return trim($v);
    }
    protected function _read_single_line_doc()
    {
        $v = $this->_read_comment();
        return $v;
    }

    /**
     * get root data
     * @return mixed 
     */
    public function _get_root_data()
    {
        if ($this->m_data && $this->m_data->isProvided()) {
            return $this->m_data->entry;
        }
        if ($this->m_listener) {
            if ($rdata = $this->m_listener->query()) {
                return GraphQlData::Create($rdata)->entry;
            }
        }
        return null;
    }


    public function updateField()
    {
        // if parent is indexed array
        //      - iterate in array component and get scoped value 
        // else 
        //      - invoke parent field data - and target property section
    }

    /**
     * invoke listener method
     */
    public function invokeListenerMethod($name, $args, $data=null, 
        ?GraphQlReadSectionInfo $section=null,
        string $type_call='query')
    {
        if (is_null($this->m_listener)){
            throw new GraphQlSyntaxException(sprintf('missing Object Inspector to handle [%s] operation.', $name));
            // return [
            //     ['name'=>'Sangoku','page'=>'4445'],
            //     ['name'=>'Tintin','page'=>'4445'],
            // ];
        }
        $r_name = $name;
        $v_injector = Dispatcher::GetInjectTypeInstance(GraphQlQueryOptions::class, null);
        $fc = new ReflectionMethod($this->m_listener, $r_name);
        $v_type = $type_call ?? ($this->p_loadInput ? $this->p_loadInput->type : null) ?? 'query'; 
        $params = $this->_getMethodParameter($args, $v_type, $v_injector);
        $v_injector->data = $data;
        $v_injector->setContext($v_type);
        $pc = count($params);
        $tc = $fc->getNumberOfRequiredParameters();
        if ($pc < $tc) {
            igk_die(sprintf('missing required parameter %s. expected %s', $pc, $tc));
        }
        $v_closure = \Closure::fromCallable([$this->m_listener, $r_name])
        ->bindTo($this->m_listener);
        $v_injector->setSection($section);
        $v_injector->setCallable($v_closure);
        $params = Dispatcher::GetInjectArgs($fc, $params);
        $cvalue = $this->m_listener->$r_name(...$params);
        return $cvalue;
    }
    /**
     * store parameter request 
     * @param mixed $params 
     * @param string $type 
     * @param null|GraphQlQueryOptions $request 
     * @return array 
     * @throws IGKException 
     */
    private function _getMethodParameter($params, $type = 'query', ?GraphQlQueryOptions $request = null)
    {
        $request && $request->clear();
        if (is_null($params)) {
            return [];
        }
        $tab = [];
        $v_variables = $this->m_variables;
        foreach ($params as $t => $v) {
            $d = null;
            if ($type == 'mutation') {
                $d = $v;
                if ($v[0] == '$') {
                    $tn = substr($v, 1);
                    if (key_exists($tn, $v_variables)) {
                        $d = $v_variables[$tn];
                    }
                }
            } else {
                if ($t[0] == '$') {
                    $tn = substr($t, 1);
                    $d = (is_object($v) || is_array($v) ? igk_getv($v_variables, $tn) : null) ?? igk_getv($v, 'default');
                } else {
                    $d = $v;
                }
            }
            $tab[] = $d;
            $request->store($t, $d);
        }

        return $tab;
    }

    /**
     * expose read name
     * @return string 
     */
    public function readName()
    {
        return $this->_read_name();
    }
}
