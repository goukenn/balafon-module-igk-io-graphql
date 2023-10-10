<?php

// @author: C.A.D. BONDJE DOUE
// @filename: GraphQl
// @date: 20221104 19:24:33
// @desc: 
namespace igk\io\GraphQl;

use Exception;
use IGK\Actions\Dispatcher; 
use IGK\System\IO\Configuration\ConfigurationReader;

use IGKException;
use ReflectionException;
use ReflectionMethod;

/**
 * parse custom graphQL 
 * @package igk\io\GraphQl
 * @deprecated use GraphQlParse2 insteed
 */
class GraphQlParser
{
    private $m_text;
    private $m_token;
    private $m_offset = 0;
    private $m_readMode = self::READ_NAME;
    private $m_listener;
    private $m_declared_types = [];
    private $m_declaredInputs; 
    /**
     * variables to pass
     * @var mixed
     */
    var $variables;

    /**
     * @var igk\io\GraphQl\mapData
     */
    var $mapData;



    const READ_NAME = 0;
    const READ_READ_TYPE = 1;
    const READ_READ_DEFAULT = 2;
    const READ_ARGUMENT = 3;
    const READ_END_ARGUMENT = 4;
    const READ_DEFINITION = 5;

    const T_GRAPH_START = 1;
    const T_GRAPH_END = 2;
    const T_GRAPH_NAME = 3;
    const T_GRAPH_TYPE = 4;
    const T_GRAPH_DEFAULT = 5;
    const T_GRAPH_ARGUMENT = 6;
    const T_GRAPH_COMMENT = 7;
    const T_GRAPH_INTROSPECTION = 8;
    const T_GRAPH_DECLARE_TYPE = 9;
    const T_GRAPH_DECLARE_INPUT = 10;
    const T_GRAPH_TYPE_DEFINITION = 11;
    const T_GRAPH_MULTI_STRING = 12;
    const T_GRAPH_STRING = 13;
    const T_GRAPH_SPREAD_OPERATOR = 14;
    const LITTERAL_TOKEN = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789_';
    const RESERVED_WORD = 'true|false|null|schema|query|type|enum|mutation';
    const SPEAR_OPERATOR = '...';


    const T_TYPE_QUERY_FUNC = 'query_func';

    const HOOK_LOADING_COMPLETE = __CLASS__.':hook_loading_complete';

    /**
     * retrieve declared inputs
     * @return mixed 
     */
    public function getDeclaredInputs()
    {
        return $this->m_declaredInputs;
    }
    /**
     * get the read token
     * @return mixed 
     */
    public function getToken(){
        return $this->m_token;
    }
    /**
     * get declared fragment
     * @return array 
     */
    public function getFragments(){
        return igk_getv($this->m_declaredInputs, 'fragment'); 
    }
    /**
     * create parsing info object 
     * @return object 
     */
    private function _create_info()
    {
        return (object)[
            "name" => null, //  <- name of the object 
            "type" => null, //  <- type of the object null|query|mutation|func_type
            "default" => null,
            "description" => null,
            "parent" => null,
            "alias" => null
        ];
    }
    /**
     * parse graph query string and load data 
     * @param string|array $graph graph data
     * @return false|object
     */
    public static function Parse($graph, $data = null, $listener = null, &$parser = null)
    {
        $o = null;
        $parser = new static;
        $parser->m_listener = $listener;
        $query = $graph;
        $variables = [];
        if (is_array($graph) || is_object($graph)) {
            $query = igk_getv($graph, 'query');
            $variables = igk_getv($graph, 'variables');
        }
        $parser->variables = $variables;
        try {
            $parser->_load($query, $data, $o);
            return (object)$o;
        }
        catch(ReflectionException $ex){
            throw new GraphQlSyntaxException('missing method', 501, $ex);
            
        } catch (Exception $ex) {
            throw new GraphQlSyntaxException($ex->getMessage(), 500, $ex);
        
        }
        return false;
    }
    protected function _get_model_callback()
    {
        return function ($tn) {
            return $this->getMapData($tn);
        };
    }
    protected function _update_chain_args(GraphQlChainGraph $chain_args, $id, $v, & $chain_start){
        // igk_wln("id: ".$id." : ".$v);
        $chain_args->update($id, $v, $chain_start);     
    }
    /**
     * load data 
     * @param string $graph graph query
     * @param mixed $data initial data
     * @param mixed referenced output
     * @Exception can raise exception
     */
    protected function _load(string $graph, $data = null, &$o = null)
    {
        $parser = $this;
        $parser->m_text = $graph;
        $q = [];  // 
        $p = [];  // container
        // initialize info
        $f_info = $parser->_create_info();
        // - init data - to store initialized data 
        $v_init_data = $data;
        $data = $data ? GraphQlData::Create($data): new GraphQLData;
        $v_init_graph_obj = !is_null($v_init_data); // <- mark to initiliaze the first object 

        $v_start = false;
        $v_ldinput = null;
        $v_declaredInputs = & $this->m_declaredInputs;
        $v_declaredTypes = & $this->m_declared_types;
        $v_def_name = false;
        $v_description = null;
        $v_alias = null;
        $v_outlist = null;
        $v_injector = Dispatcher::GetInjectTypeInstance(GraphQlQueryOptions::class,null);
        // + | to store property in chain list 
        $v_chain_args = new GraphQlChainGraph;
        $v_chainstart = false;

        $v_model_callback = $this->_get_model_callback();
        while ($parser->read()) {
            $v = $e = $parser->token();
            $id = null;
            if (is_array($e)) {
                $id = $e[0];
                $v = $e[1];
            }
            switch ($v) {
                case ':': // explicit separator
                case ' ': // separator 
                    // define alias
                    if ($f_info) {
                        // possibility of read alias
                        $v_alias = true;
                        // $parser->m_readMode = self::READ_NAME;
                        continue 2;
                    }
                    break;
            }
            $this->_update_chain_args($v_chain_args, $id, $v, $v_chainstart);
            switch ($id) {
                    // handle query expression      
                case self::T_GRAPH_MULTI_STRING:
                    $v_description = trim($v, '" ');
                    break;
                case self::T_GRAPH_STRING:
                    /* out of context else string declaration - constant var=""*/
                    $v_description = trim($v, '" ');
                    break;
                case self::T_GRAPH_SPREAD_OPERATOR:
                    $this->_load_spread_info($f_info, $e, $o, $p, $q, $data, $v_description);

                    break;
                case self::T_GRAPH_START:
                    $v_def_name = false;
                    if (is_null($o)) {
                        // first object
                        $o = [];
                        $v_start = 1;
                    } else {
                        igk_debug_wln("add - parent ");
                        if (!empty($f_info->name)) {
                            $n = trim($f_info->name);
                            $o[$n] = [];
                            $q[] = &$o;
                            $o = &$o[$n];
                            $f_info = $parser->_create_info();
                        } else {
                            throw new GraphQlSyntaxException("graph start - no info_name defined no name defined - " . json_encode($o));
                        }
                        $v_start++;
                    }
                    break;
                case self::T_GRAPH_END:
                    // + | for every } command check that the data is already provided - 
                    if ($this->_end_graph_end($data)){
                        $v_init_graph_obj = true;
                    }

                    if ($v_init_graph_obj){
                        $this->_update_value($o, $data);
                        $v_init_graph_obj = false;
                    }

                    if (!empty($n = $f_info->name)) {
                        $this->_update_mark($o, $f_info, $data, $v_chain_args->path());
                    }
                    
                    if (($data instanceof GraphQlData) && $this->m_listener && !$v_ldinput && !$v_init_data) {
                        // load entry fields with listener - no data to init
                        $tfilter = (array)$o;
                        // check that named property as set
                        $set = false;
                        foreach($tfilter as $k=>$v){
                            if (!is_numeric($k)){
                                if ($v!==null){
                                    $set = true;
                                    break;
                                }
                            }
                        }

                        if (!$set){ 
                            $t_entry = $this->m_listener->query();
                            $data->storeEntry($t_entry);
                            if ($t_entry  && !$data->isIndex()) {
                                $v_data = $this->getData($q, $t_entry);
                                // update query entries 
                                foreach (array_keys($o) as $k) {
                                    if (is_numeric($k)){
                                        $v_tobj = $o[$k]; 
                                        $v_tobj->updateFields($this, $k, $o, $data, $v_data, $this->_get_model_callback());
                                    } else {
                                        $o[$k] = $data->getValue($k,
                                        $v_data,
                                        //  $t_entry,
                                         $this->_get_model_callback());      
                                    }
                                }
                            }
                        }
                    }
                    $data->updateObjectField($o, $v_chain_args->path(), $v_model_callback); 

                    if (($c = count($q)) > 0) {
                        $o = &$q[$c - 1];
                        array_pop($q);
                    }

                    if (count($p) > 0) {
                        $data = array_pop($p);
                    }
                    $v_start--;
                    if ($v_start === 0) {
                        $v_start = false;
                    }
                    if ($v_ldinput != null) {
                        $v_ldinput->definition = $data->getInfo();
                        // update root query request 
                        if (!$v_start && ($v_ldinput->type == 'query')) {
                            if (!$v_outlist)
                                $v_outlist = [];
                            if (!empty($v_ldinput->name)) {
                                $v_outlist[$v_ldinput->name] = $o;
                                $o = null;
                            }
                        }
                        $v_ldinput = $v_ldinput->parent;
                    }
                    break;
                case self::T_GRAPH_TYPE:
                    $f_info->type = $v;
                    break;
                case self::T_GRAPH_DEFAULT:
                    $f_info->default = $v;
                    break;
                case self::T_GRAPH_NAME:
                    if ($v_start) {
                        if (!empty($f_info->name)) {
                            if ($v_alias) {
                                // move alias to target
                                $f_info->alias = $f_info->name;
                                $f_info->name = $v;
                                $v_alias = false;
                                break;
                            }
                            $this->_update_mark($o, $f_info, $data, $v_chain_args->basePath());
                            $v_init_graph_obj = false;
                        }                       
                        $f_info->name = $v;
                        $f_info->description = $v_description;
                        $v_description = null;
                    } else {
                        if ($v_ldinput && $v_def_name) {
                            $v_ldinput->name = $v;
                            $v_def_name = false;
                        } else if ($tn = $this->_get_token($v)) {
                            // create definition - 
                            if ($tn === self::T_GRAPH_DECLARE_INPUT) {
                                $v_pinput = $v_ldinput;
                                $v_ldinput = GraphQlDeclaredInputFactory::Create($v)  ?? igk_die('missing declared input name [' . $v . ']');
                                
                                if (!isset($v_declaredInputs[$v])){
                                    $v_declaredInputs[$v] = [];
                                }
                                
                                $v_declaredInputs[$v][] = $v_ldinput;
                                $v_def_name = true;
                                $v_ldinput->parent = $v_pinput;
                                if ($v_description) {
                                    $v_ldinput->description = $v_description;
                                }
                                $v_description = null;
                                if ($v_ldinput->readDefinition($this)){
                                    $v_ldinput = $v_ldinput->parent;
                                }
                            }
                            if ($tn === self::T_GRAPH_DECLARE_TYPE){
                                $v_pinput = $v_ldinput;
                                $v_type_name = '';
                                if ($this->read()){
                                    $e = $this->m_token;
                                    if ($e[0] == self::T_GRAPH_NAME){
                                        $v_type_name = $e[1];
                                    }
                                }else{
                                    throw new GraphQlSyntaxException('missing type name');
                                }
                                $v_ldinput = new GraphQlDeclaredType();
                                $v_ldinput->name = $v_type_name;
                                $v_declaredTypes[$v_type_name] = $v_ldinput;
                                $v_def_name = true;
                                // $v_ldinput->parent = $v_pinput;
                                if ($v_description) {
                                    $v_ldinput->description = $v_description;
                                }
                                $v_description = null;
                                // $o = null;
                                $this->_read_type_definition($v_ldinput);
                            }
                        }
                    }
                    break;
                case self::T_GRAPH_TYPE_DEFINITION:
                    $f_info->type = $v["type"];
                    $f_info->default = $v["default"];
                    $f_info->description = $v_description;
                    $this->_update_mark($o, $f_info, $data, null); 
                    $v_description = null;
                    break;
                case self::T_GRAPH_ARGUMENT:
                    // Agument for reading info
                    if ($v_ldinput && !$f_info) {

                        // $v_n = $v_ldinput->name;
                        // depending on type
                        if ($v_ldinput->type == 'query') {
                            // passing definition type 
                            $v_ldinput->setParameter($v);
                            break;
                        }
                    }
                    if (!empty($n = $f_info->name)) {
                        $tab = $this->_get_field_info($n);
                        $n = $tab["name"];
                        // must call argument
                        $cvalue = null;
                        $r_name = igk_getv($e, 2) ?? $n;
                        if ($this->m_listener) {
                            // get graph data 
                            $fc = new ReflectionMethod($this->m_listener, $r_name);
                            $v_type = $v_ldinput ? $v_ldinput->type : 'query'; 
                            $params = $this->_getMethodParameter($v, $v_type, $v_injector);
                            $pc = count($params);
                            $tc = $fc->getNumberOfRequiredParameters();
                            if ($pc < $tc) {
                                igk_die(sprintf('missing required parameter %s. expected %s', $pc, $tc));
                            } 
                            $params = Dispatcher::GetInjectArgs($fc, $params);
                            $cvalue = $this->m_listener->$r_name(...$params); //(array)$v);
                        }
                        array_push($p, $data);
                        $data = GraphQlData::Create($cvalue);
                        $v_alias = false;
                        $f_info->type = self::T_TYPE_QUERY_FUNC;
                    }
                    break;
            }
        }
        if (!empty($f_info->name)) {
            // + | last update mark
            $this->_update_mark($o, $f_info, $data, $v_chain_args->path());
        } 
        if ($v_alias) {
            $v_alias = false;
        }
        if ($v_outlist) {
            $o = $v_outlist;
        }

        igk_hook(self::HOOK_LOADING_COMPLETE, [$this, & $o]);
    }

    /**
     * end graph reading to update value 
     * @param mixed $o 
     * @param GraphQlData $data 
     * @return bool 
     */
    protected function _end_graph_end(GraphQlData $data):bool{
        if ($data->isProvided()){
            return false;
        }
        if ($this->m_listener){
            $v_qo = $this->m_listener->query();
            $data->storeEntry($v_qo);
            return true;
        }
        return false;


    }

    public function getData($q, $data) {
        $o = $data;
        while($o && ( count($q)>0)){
            //$o = array_shift($q);
            $o = array_pop($q);
            break;
        }
        return $o;
    }

    /**
     * read type definition 
     * @param mixed $definition 
     * @return void 
     * @throws IGKException 
     */
    protected function _read_type_definition($definition){
        // + | --------------------------------------------------------------------
        // + | type typeName{ \
        // + |      field: Type[!]|[Type][!]|=
        // + | }
        // + |
        
        $v_level = 0;
        $v_end = false;
        while(!$v_end && $this->read()){
            $e = $this->m_token;
            switch($e[0]){
                case self::T_GRAPH_END:
                    $v_level--;
                    if ($v_level===0){
                        $v_end = true;
                    }
                    break;
                case self::T_GRAPH_START:
                    $v_level++;
                    break;
            }
        }
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
        $tab = [];
        foreach ($params as $t => $v) {
            $d = null;
            if ($type == 'mutation') {
                $d = $v;
                if ($v[0] == '$') {
                    $tn = substr($v, 1);
                    if (key_exists($tn, $this->variables)) {
                        $d = $this->variables[$tn];
                    }
                }
            } else {
                if ($t[0] == '$') {
                    $tn = substr($t, 1);
                    $d = (is_object($v) || is_array($v) ? igk_getv($this->variables, $tn) : null) ?? igk_getv($v, 'default');
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
     * convert string to alias name
     * @param string $n 
     * @return array 
     */
    protected function _get_field_info(string $n)
    {
        $tab = explode(' ', $n);
        $alias = $name = array_pop($tab);
        if (count($tab) > 0) {
            $alias = array_shift($tab);
        }
        return compact("name", "alias");
    }

    /**
     * load spread info
     * @return void 
     */
    protected function _load_spread_info(& $f_info , $e , & $o, & $p, & $q, $data, ?string $v_description=null){
        $v_name = $e[2]; 
        $v_s = new GraphQlSpreadInfo($v_name);  
        $o[] = $v_s;
        // go to chain properties to update fields
    }
    protected function _update_mark(&$o, &$f_info, $data, ?string $path =  null)
    {
        $n = $f_info->name;
        if (empty($n)) {
            igk_die("name is empty");
        }
        $v_data = $data ? $data->first() : null; 
        $data->storeInfo($n, $f_info);
        $v = $f_info->default;
        if ($v_data && $path && !$data->isIndex()){
            // chain path 
            $v_data = igk_conf_get($v_data, $path); 
            if (is_array($v_data)){
                $v_data = igk_getv(array_values($v_data), 0);
            }
        }

        // + | update last info values
        if (!is_null($v_data)) {
            $v = $data->getValue($f_info->name, $v_data, $this->_get_model_callback());
        }
        $o[$n] = $v;
        // + | create new info
        $f_info = $this->_create_info();  
        return $n;
    }
    protected function _update_value(& $o, GraphQlData $data){
        $infos = $data->getInfo();
        $v_data = $data->first();
        $v_callback = $this->_get_model_callback();
        foreach($o as $k=>$v){
            if (is_numeric($k)){
                $v->updateFields($this, $k, $o, $data, $v_data, $v_callback);
            }
        }
           // + | update last info values
        foreach($infos as $k=>$f_info){
            $v = $data->getValue($f_info->name, $v_data, $v_callback);
            $o[$k] = $v;
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
        return igk_getv($this->mapData, $typeName);
    }
    /**
     * 
     * @return void 
     */
    protected function __construct()
    {
    }

    protected function _export_arg_definition(string $l, ?string $name = null)
    {
        $r = new ConfigurationReader;
        $r->delimiter = ',';
        $r->separator = ':';
        // with bracket treat argument expression
        $r->escape_start= '[';
        $r->escape_end= ']'; 
        // + | transform inline array definition to expression of json - decoding
        $l = $r->treatExpression($l, $expression);
     
        $o = $r->read($l);
        foreach ($o as $k => $v) {
            if (is_null($v)){
                continue;
            }
            $p = explode('=', $v, 2);
            if (count($p) >= 2) {
                $d = trim($p[1]);
                $t = trim($p[0]);
                if (is_numeric($d)) {
                    $d = floatval($d);
                }
                $o->$k = ['default' => $d, 'type' => $t, 'directive' => null];
            }
            if ($expression && key_exists($v, $expression)){
                $o->$k = json_decode($expression[$v]);
            }
        }
        // read argument
        $this->m_token = [self::T_GRAPH_ARGUMENT, $o, $name];
        $this->m_readMode = self::READ_NAME;
        return true;
    }
    /**
     * read add move next
     * @return bool 
     * @throws IGKException 
     */
    public function read(): bool
    {
        $pos = &$this->m_offset;
        $ln = strlen($this->m_text);
        $l = "";
        $skip = false;

        while ($pos < $ln) {
            $ch = $this->m_text[$pos];
            $pos++;
            switch ($this->m_readMode) {
                case self::READ_ARGUMENT:
                    $l = $ch . $this->_read_argument($pos, $ln);
                    $pos++;
                    return $this->_export_arg_definition($l);
                    // $r = new ConfigurationReader;
                    // $r->delimiter = ',';
                    // $r->separator = ':';
                    // $o = $r->read($l);
                    // // read argument
                    // $this->m_token = [self::T_GRAPH_ARGUMENT, $o];
                    // $this->m_readMode = self::READ_NAME;
                    // return true;
                case self::READ_DEFINITION:
                    $l = $ch . $this->_read_definition($pos, $ln);
                    if (preg_match("/(?P<n>[^\(]+\()/", $l)) {
                        // is method declaraion 
                        $tpos = strpos($l, '(');
                        $n = trim(substr($l, 0, $tpos));
                        $g = trim(igk_str_read_brank($l, $tpos, ')', '('), '()');
                        return $this->_export_arg_definition($g, $n);
                    }


                    $r = new ConfigurationReader;
                    $o = $r->read($l);
                    $type = $o ? array_keys((array)$o)[0] : null;
                    $value = null;
                    if ($type && $this->_is_know_type($type)) {
                        if (is_null($value = $o->$type)) {
                            $value = $this->_get_known_default_value($type);
                        } else {
                            $value = $this->_get_known_value($type, $value);
                        }
                    } else {
                        if (!$type || is_null($value = $o->$type)) {
                            // constant value
                            $value = $type;
                            $type = 'mixed';
                        }
                    }
                    $this->m_token = [self::T_GRAPH_TYPE_DEFINITION, ['type' => $type, 'default' => $value]];
                    $this->m_readMode = self::READ_NAME;
                    return true;
            }

            switch ($ch) {
                case '.':
                    if (($v_spead = $ch . substr($this->m_text, $pos, 2)) == self::SPEAR_OPERATOR) {
                        $v_d = [self::T_GRAPH_SPREAD_OPERATOR, $v_spead];
                        $pos += 2;
                        $v_name = $this->_read_name($pos);
                        if (empty($v_name)){
                            throw new GraphQlSyntaxException("spear operation missing name");
                        }
                        $v_d[] = $v_name;
                        $this->m_token = $v_d;
                        return true;
                    }
                    break;
                case '"':
                    // detect multi cell description 
                    if ($ch . substr($this->m_text, $pos, 2) === '"""') {
                        $noffset = $pos + 2;
                        if (($cpos = strpos($this->m_text, '"""', $noffset)) === false) {
                            // missing close tag
                            return false;
                        }
                        $v = $ch . substr($this->m_text, $pos, $cpos + 2);
                        $this->m_token = [self::T_GRAPH_MULTI_STRING,  $v];
                        $pos = $cpos + 3;
                        return true;
                    }
                    $v = $ch . igk_str_read_brank($this->m_text, $pos, $ch, $ch);
                    $this->m_token = [self::T_GRAPH_STRING,  $v];
                    $pos++;
                    return true;
                case "#":
                    $e = strpos($this->m_text, "\n", $this->m_offset);
                    if ($e !== false) {
                        $v = substr($this->m_text, $pos, $e - $pos);
                        $pos = $e + 1;
                    } else {
                        $v = substr($this->m_text, $pos);
                        $pos = $ln + 1;
                    }
                    $this->m_token = [self::T_GRAPH_COMMENT, $ch . $v];
                    return true;
                case '{':
                    $pv = trim($l);
                    if (!empty($pv) && $this->_handle_name($pv, $pos))
                        return true;
                    $this->m_token = [self::T_GRAPH_START, $ch];
                    return true;
                case '}':
                    if (!empty($pv = trim($l))) {
                        $pos--;
                        $this->m_token = [self::T_GRAPH_NAME, $pv];
                        return true;
                    }
                    $this->m_token = [self::T_GRAPH_END, $ch];
                    return true;
                case ':':
                    if ($this->_handle_name($l, $pos))
                        return true;
                    $this->m_token = $ch;
                    $this->m_readMode = self::READ_DEFINITION;
                    return true;
                case ' ':
                    if (!$skip) {
                        $pos++;
                        if ($this->_handle_name($l, $pos))
                            return true;
                        $pos--;
                        $l .= $ch;
                    }
                    $skip = true;
                    break;
                case '(': // start reading argument 
                    if ($this->_handle_name($l, $pos))
                        return true;
                    $this->m_token = $ch;
                    $this->m_readMode = self::READ_ARGUMENT;
                    return true;
                case ')': // end read argument
                    $this->m_token = $ch;
                    $this->m_readMode = self::READ_END_ARGUMENT;
                    return true;
                default:
                    $ip = strpos(self::LITTERAL_TOKEN, $ch);
                    if ($ip !== false) {
                        $l .= $ch;
                        $skip = false;
                    } else {
                        if ($this->m_readMode == self::READ_NAME) {
                            $n = trim($l);
                            if (!empty($n)){
                                if (strpos($n, "__") === 0) {
                                    $this->m_token = [self::T_GRAPH_INTROSPECTION, $n];
                                    return true;
                                }
                                $token = $this->_get_token($n) ?? self::T_GRAPH_NAME;
                                $this->m_token = [$token, $n];
                                return true;
                            }
                        }
                    }
                    break;
            }
        }
        return false;
    }
    protected function _igk_get_know_parser($type)
    {
        $cl = __NAMESPACE__ . '\\GraphQl' . $type . 'Parser';
        if (class_exists($cl)) {
            $cl = new $cl;
            return $cl;
        }
    }
    protected function _get_known_value($type, $value)
    {
        if ($parser = $this->_igk_get_know_parser($type)) {
            return $parser->parse($value);
        }
        return $value;
    }
    protected function _get_known_default_value($type)
    {
        return igk_getv([
            "String" => "",
            "Int" => 0,
            "Float" => 0.0,
            "Double" => 0.0,
            "Date" => date("Y-m-d"),
            "DateTime" => date("Y-m-d H:i:s"),
        ], $type);
    }
    protected function _is_know_type($t)
    {
        return in_array($t, array_merge(explode('|', 'String|Int|Float|Double|Date|DataTime'), $this->m_declared_types));
    }
    public function token()
    {
        return $this->m_token;
    }
    protected function _handle_name($l, &$pos): bool
    {
        if (!empty($l)) {
            $pos--;
            $this->m_token = [self::T_GRAPH_NAME, trim($l)];
            return true;
        }
        return false;
    }
    protected function _read_definition(&$pos, $ln)
    {
        $v = "";
        while ($pos < $ln) {
            $ch = $this->m_text[$pos];
            $pos++;
            switch ($ch) {
                case '{':
                case '}':
                case "\n":
                case ',':
                    $pos--;
                    return $v;
            }
            $v .= $ch;
        }
        return $v;
    }
    /**
     * 
     * @param mixed $pos 
     * @param mixed $ln 
     * @return string 
     */
    protected function _read_argument(&$pos, $ln)
    {
        $v = "";
        $depth = 1;
        while ($pos < $ln) {
            $ch = $this->m_text[$pos];
            $pos++;
            switch ($ch) {
                case '(':
                    $depth++;
                case ')':
                    $depth--;
                    if ($depth == 0) {
                        $pos--;
                        return $v;
                    }
                    break;
            }
            $v .= $ch;
        }
        return $v;
    }
    /**
     * is token reserver words
     * @param string $n 
     * @return mixed 
     * @throws IGKException 
     */
    protected function _get_token(string $n)
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
        ], $n);
    }

    protected function _read_name(int & $pos){
        $v_length  =  strlen($this->m_text);
        $v_result = '';
        while($pos < $v_length){
            $ch = $this->m_text[$pos];
            $ip = strpos(self::LITTERAL_TOKEN, $ch);

            if ($ip === false){
                break;
            }
            $pos++;
            $v_result .=$ch;
        }
        return $v_result;
    }  
    
    /**
     * read scope name
     * @return null|string 
     */
    public function readName():?string{
        $pos = & $this->m_offset;

        return $this->_read_name($pos);

    }
    
}
