<?php
// @author: C.A.D. BONDJE DOUE
// @file: GraphQlParser2.php
// @date: 20231009 16:35:22
namespace igk\io\GraphQl;

use IGK\Actions\Dispatcher;
use igk\io\GraphQl\GraphQlReaderConstants as rConst;
use IGK\System\IO\Configuration\ConfigurationReader;
use IGK\System\IO\Path;
use IGKException;
use ReflectionMethod;
use stdClass;

///<summary></summary>
/**
 * 
 * @package igk\io\GraphQl
 */
class GraphQlParser2
{
    const T_GRAPH_DECLARE_TYPE = 9;
    const T_GRAPH_DECLARE_INPUT = 10;

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
     * @var mixed
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
    private $m_loadInput;

    /**
     * get declared input 
     * @return ?array
     */
    public function getDeclaredInputs():?array{
        return $this->m_declaredInput; 
    }

    protected function __construct()
    {
    }
    /**
     * parse query and 
     * @param string|array $query in case of array
     * @param mixed $root 
     * @param mixed $listener 
     * @param mixed $reader reader for debugin 
     * @return mixed 
     */
    public static function Parse($query, ?array $root = null, $listener = null, &$reader = null, ?GraphqlMapData $mapData = null)
    {
        $query = $query;
        $variable = null;
        if (is_array($query)) {
            $variable = igk_getv($query, 'variables');
            $query = igk_getv($query, 'query') ?? igk_die('missing query');;
        }

        $reader = new static;
        $reader->m_query = $query;
        $reader->m_variables = $variable;
        $reader->m_data = $root ? GraphQlData::Create($root) : $root;
        $reader->m_listener = $listener;
        $reader->m_length = strlen($query);
        $reader->m_mapData = $mapData;
        $o = $reader->_load();
        return $o;
    }
    /**
     * load and parse data 
     * @return null|array|object 
     */
    protected function _load()
    {
        /**
         * @var ?GraphQlReadSectionInfo $v_section_info 
         */
        $o = null;
        $v_brank = 0;
        /**
         * @var ?GraphQlReadSectionInfo $v_section_info 
         */
        $v_section_info = null;     // <- section reading info
        $v_desc = null;             // <- preload reading description 
        $v_property_name = null;    // <- store the current property name to load
        $v_property_alias = null; // <- store property alias
        $p = []; // container 
        $v_chain_args = new GraphQlChainGraph;
        $v_chainstart = false;
        $v_property_info = null;  // <- store property info
        $data = $this->_get_root_data(); // <- init root data
        $v_list = null;
        $v_fc_clear = function () use (&$v_property_info, &$v_property_name, &$v_property_alias) {
            $v_property_info = null;
            $v_property_name = null;
            $v_property_alias = null;
        };
        $v_model_mapping = $this->_get_model_callback();
        $v_out_data = null;
        $v_def_name = false;
        $v_current_data_def = null;
        $v_key =
            $v_p = null;

        $v_data_is_indexed = GraphQlData::IsIndexed($data);
        $v_load_indexed = false;
        $v_index_list = false;
        while ($this->read()) {
            $id = $v = $e = $this->token();
            if (is_array($e)) {
                $id = $e[0];
                $v = $e[1];
            }
            $this->_update_chain_args($v_chain_args, $id, $v, $v_chainstart);

            switch ($id) {
                case rConst::T_READ_FUNC_ARGS:
                    if ($v_property_info) {
                        // replace with callback function if section is depending on 
                        // depend on global variable or depending on context view - require a listener to handle function 
                        if ($this->m_listener) {
                            $args = $v;

                            $v_property_info->type = GraphQlPropertyInfo::TYPE_FUNC;
                            $v_property_info->args = $args;
                            $v_key = $v_property_info->getKey();
                            // $v_section_info = $this->_chain_info($v_section_info);
                            if (is_null($v_property_info->section->parent)) {
                                if (is_null($args)) {
                                    // direct invocation
                                }
                            } else {
                                $o[$v_key] = new GraphQlReferenceSectionProperty($this, $o, $v_key, $v_section_info, $v_property_info);
                            }
                        }
                    }
                    break;
                case rConst::T_READ_DESCRIPTION:
                    $v_desc = $v;
                    break;
                case rConst::T_READ_DIRECTIVE:
                    if ($v_property_info) {
                        $v_property_info->directives[] = $v;
                    } else if ($v_section_info) {
                        $v_section_info->directives[] = $v;
                    } else {
                        throw new GraphQlSyntaxException('directive not allowed');
                    }
                    break;
                case rConst::T_READ_SPEAR:
                    $name = $e[2];
                    // new property and name
                    $this->_update_last_property($o, $v_property_info, $data);
                    $v_property_info = $this->_add_new_property($name, $v_desc, $v_property_alias,$v_section_info);
                    $v_property_info->type = GraphQlPropertyInfo::TYPE_SPEAR; 
                    $sp = new GraphQlSpreadInfo($this, $o, $name, $v_property_info);
                    // + | remove section from object data
                    unset($v_section_info->properties[$name]);

                    $o[] = $sp;
                    $v_sp = array_key_last($o);
                    $sp->key = $v_sp;
                    $v_property_name = null;

                    $v_section_info->properties[$name] = new GraphQlSpreadIndex($v_sp, $v_property_info);
                    $v_fc_clear();
                    break;
                case rConst::T_READ_NAME:
                    if ($v_brank == 0) {
                        if ($v_def_name) {
                            $this->m_loadInput->name = $v;
                            $v_def_name = false;
                        } else {
                            // top brank reading definition property 
                            $this->_bind_delcaredInput($v, $v_def_name);
                        }
                    } else {
                        if (!$v_section_info) {
                            throw new GraphQlSyntaxException('read name but no section found');
                        }

                        $this->_update_last_property($o, $v_property_info, $data);
                        $v_property_info = $this->_add_new_property($v, $v_desc, $v_property_alias,$v_section_info);
                        $v_property_alias = null;
                        $v_desc = null;
                        $v_property_name = $v;
                    }
                    break;
                case rConst::T_READ_ALIAS:
                    // read field alias
                    if (!empty($v_property_alias)) {
                        throw new GraphQlSyntaxException('already set alias');
                    }
                    $v_property_alias = $v;
                    break;
                case rConst::T_READ_START:
                    $v_bind_section = false;
                    if ($v_brank == 0) {
                        if ($this->m_loadInput) {
                            // for query define object entry name-wait for exit to store it on field info
                        }


                        if (!is_null($o)) {
                            if (is_null($v_list)) {
                                $v_list = [];
                                $v_list[] = $o;
                                unset($o);
                                $o = null;
                            }
                            $o = [];
                            $v_list[] = $o;
                            $o = &$v_list[array_key_last($v_list)];
                        } else {
                            // + | start object creation
                            $o = [];
                        }
                        $v_current_data_def = new GraphQlPointerObject($o);
                    } else {
                        // + | sub properties
                        $v_key = $v_property_info->getKey();
                        $this->_init_child_property($o, $v_key, $v_property_info, $data);
                        $v_section_info->name = $v_property_name;
                        $v_section_info->alias = $v_property_alias;

                        $v_section_info = $this->_chain_info($v_section_info);
                        $o[$v_key] = new GraphQlReferenceSectionProperty($this, $o, $v_key, $v_section_info, $v_property_info);
                        $v_new_o = [];
                        $v_current_data_def = new GraphQlPointerObject($v_new_o, $v_current_data_def);
                        $o = &$v_new_o;
                        $v_bind_section = true;
                        if ($v_property_info->type == GraphQlPropertyInfo::TYPE_FUNC) {
                            $v_section_info->type = $v_property_info->type ;
                        }
                    }
                    $v_brank++;
                    // start 
                    if (!$v_bind_section) {
                        $v_section_info = $this->_chain_info($v_section_info);
                    }
                    $v_fc_clear();
                    break;
                case rConst::T_READ_END:
                    if (!$v_section_info) {
                        throw new GraphQlSyntaxException("missing section");
                    }
                    if (empty($v_section_info->properties)) {
                        throw new GraphQlSyntaxException("missing property in section");
                    }
                    $this->_update_last_property($o, $v_property_info, $data);
                    if ($v_section_info->type == GraphQlPropertyInfo::TYPE_FUNC) {
                        // invoke function and 
                        igk_hook(GraphQlHooks::HookName(GraphQlHooks::HOOK_SECTION_FUNC), [$this, $v_section_info, $data]);
                    } else {
                        if (($v_data_is_indexed)&& empty($o)) {
                            $tb = $data->entry;
                            $o = $v_section_info->getData($tb, $v_model_mapping);
                            $v_load_indexed = true;
                        }
                    }
                    $this->_endLoading($v_section_info, $data);

                    $v_brank--;
                    if ($v_brank == 0) {
                        $v_section_info = null;
                        if ($this->m_loadInput) {
                            if (is_null($v_list)) {
                                $v_list = [$this->m_loadInput->name => $o];
                            } else {
                                $v_list[$this->m_loadInput->name] = $o;
                            }
                            unset($o);
                            $o = null;
                        } else if ($v_load_indexed && is_array($o)) {
                            if (!$v_index_list) {
                                $v_list = $o;
                                $v_index_list = true;
                            } else {
                                $v_list[] = $o;
                            }
                            unset($o);
                            $o = null;
                        }
                    } else {
                        if ($v_section_info instanceof GraphQlReadSectionInfo) {
                            $v_section_info = $v_section_info->parent;
                        }
                    }
                    if ($v_p = $v_current_data_def->getParentRef()) {
                        $o = &$v_p->getRefData();
                        $v_current_data_def = $v_p;
                    } else {
                        // igk_wln_e("the o", $o);
                    }


                    $v_fc_clear();
                    break;
            }
        }
        if ($o || $v_list) {
            igk_hook(Path::Combine(GraphQlHooks::class, GraphQlHooks::HOOK_LOAD_COMPLETE), [$this]);
        }
        // | <- list of object 
        if (!is_null($v_list)) {
            unset($o);
            return $v_list;
        }
        return (object)$o;
    }
    protected function _add_new_property(string $name, ?string $desc, ?string $alias, GraphQlReadSectionInfo $section){
        $v_property_info = new GraphQlPropertyInfo($name);
        $v_property_info->alias = $alias;
        $v_property_info->section = $section;
        $v_property_info->description = $desc;
        $section->properties[$name] = $v_property_info;
        return $v_property_info;
    }

    protected function _init_child_property(&$o, string $v_key, GraphQlPropertyInfo $v_property_info, $data)
    {
        $v_property_info->child = true;
        $o[$v_key] = $data ? $v_property_info->createPropertyResolutionValue($data) : null;
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

        if ($v_property_info->child) {
            return;
        }
        if (!igk_key_exists($data, $v_property_info->name)) {
            throw new GraphQlSyntaxException(sprintf('missing property [%s]', $v_property_info->name));
        }
        $v_key = $v_property_info->getKey();
        $o[$v_key] = $this->_get_property_value($data, $v_property_info);
    }
    protected function _update_last_property(&$o, ?GraphQlPropertyInfo $v_property_info, $data)
    {
        if ($v_property_info && !$v_property_info->section->isDependOn()) {
            // update property info with field
            if (($data instanceof GraphQlData) && $data->isProvided() && $data->isIndex()) {
                return;
            }

            if ($data) {
                $this->_update_single_property($o, $v_property_info, $data);
            } else {
                $v_key = $v_property_info->getKey();
                $o[$v_key] = null;
            }
        }
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

    protected function _bind_delcaredInput(string $name, &$def_name)
    {
        $v_declaredInputs = &$this->m_declaredInput;
        $v_ldinput = &$this->m_loadInput;
        $def_name = false;
        $tn = $this->_check_declared_token($name);
        $v = $name;
        // create definition - 
        if ($tn === self::T_GRAPH_DECLARE_INPUT) {
            // $v_pinput = $v_ldinput;
            $v_ldinput = GraphQlDeclaredInputFactory::Create($v)  ?? igk_die('missing declared input name [' . $v . ']');

            if (!isset($v_declaredInputs[$v])) {
                $v_declaredInputs[$v] = [];
            }

            $v_declaredInputs[$v][] = $v_ldinput;
            if ($v_ldinput->readDefinition($this)) {
                $v_ldinput = $v_ldinput->parent;
            } else {

                $def_name = true;
            }
        }
        if ($tn === self::T_GRAPH_DECLARE_TYPE) {
            // $v_pinput = $v_ldinput;
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
            $v_declaredTypes[$v_type_name] = $v_ldinput;
        }
    }
    protected function _chain_info(?GraphQlReadSectionInfo $info): GraphQlReadSectionInfo
    {
        $p = $info;
        $info = new GraphQlReadSectionInfo;
        $info->parent = $p;
        return $info;
    }

    protected function _endLoading(GraphQlReadSectionInfo $info, $data)
    {
        if ($data instanceof GraphQlData){
            if ($info->parent){
                $path = $info->getFullPath();//->name
                $data = igk_conf_get($data->entry, $path);
            }

        }
        igk_hook(Path::Combine(GraphQlHooks::class, GraphQlHooks::HOOK_END_SECTION), [$this, $info, $data]);
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
            $v_in_token = strpos(rConst::T_TOKEN_NAME, $ch);
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
                        // + | check for fragment 
                    if (($v_spead = $ch . substr($this->m_query, $offset, 2)) == rConst::SPEAR_OPERATOR) {
                        $v_d = [rConst::T_READ_SPEAR, $v_spead];
                        $offset += 2;
                        $v_name = $this->_read_name($offset);
                        if (empty($v_name)){
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
                        $desc = $this->_read_single_line_doc();
                    }
                    return $this->_gen_token([rConst::T_READ_DESCRIPTION, $desc]);


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
            if (strpos(rConst::T_TOKEN_NAME, $ch) === false) {
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
        return $this->_gen_token([rConst::T_READ_FUNC_ARGS, $args]);
    }
    /**
     * export argument 
     * @param string $data 
     * @return false|stdClass 
     */
    protected function _export_arg_definition(string $data)
    {
        $v_export = new GraphQlExportArgument;
        $v_export->variables = $this->m_variables;
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
            return $this->m_data->first();
        }
        if ($this->m_listener) {
            if ($rdata = $this->m_listener->query()) {
                return GraphQlData::Create($rdata);
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


    public function invokeListenerMethod($name, $args){
        $r_name = $name;
        $v_injector = Dispatcher::GetInjectTypeInstance(GraphQlQueryOptions::class,null);
        $fc = new ReflectionMethod($this->m_listener, $r_name);
        $v_type = 'query'; // mutation
        $params = $this->_getMethodParameter($args, $v_type, $v_injector);
        $pc = count($params);
        $tc = $fc->getNumberOfRequiredParameters();
        if ($pc < $tc) {
            igk_die(sprintf('missing required parameter %s. expected %s', $pc, $tc));
        } 
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
    private function _getMethodParameter($params, $type = 'query', ?GraphQlQueryOptions $request=null)
    {
        $request && $request->clear();
        if (is_null($params)){
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
                }else {
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
    public function readName(){
        return $this->_read_name();
    }
}
