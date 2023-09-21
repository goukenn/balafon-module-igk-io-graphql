<?php

// @author: C.A.D. BONDJE DOUE
// @filename: GraphQl
// @date: 20221104 19:24:33
// @desc: 
namespace igk\io\GraphQl;

use Exception;
use IGK\Actions\Dispatcher;
use IGK\Models\ModelBase;
use IGK\System\IO\Configuration\ConfigurationReader;

use IGKException;
use ReflectionMethod;

/**
 * parse custom graphQL 
 * @package igk\io\GraphQl
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
    const T_GRAPH_INTROPECTION = 8;
    const T_GRAPH_DECLARE_TYPE = 9;
    const T_GRAPH_DECLARE_INPUT = 10;
    const T_GRAPH_TYPE_DEFINITION = 11;
    const T_GRAPH_MULTI_STRING = 12;
    const T_GRAPH_STRING = 13;
    const T_GRAPH_SPREAD_OPERATOR = 14;
    const LITTERAL_TOKEN = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789_';
    const RESERVED_WORD = 'true|false|null|schema|query|type|enum|mutation';
    const T_GRAPH_SPEAR_OPERATOR = '...';

    /**
     * retrieve declared inputs
     * @return mixed 
     */
    public function getDeclaredInputs()
    {
        return $this->m_declaredInputs;
    }
    private function _create_info()
    {
        return (object)[
            "name" => null,
            "type" => null,
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
        } catch (Exception $ex) {
            throw $ex;
        }
        return false;
    }
    protected function _get_model_callback()
    {
        return function ($tn) {
            return $this->getMapData($tn);
        };
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
        $q = [];
        $p = [];
        $f_info = $parser->_create_info();
        // init data
        $v_init_data = $data;
        $data = GraphQlData::Create($data);
        $start = false;
        $v_ldinput = null;
        $v_declaredInputs = [];
        $def_name = false;
        $v_description = null;
        $v_alias = null;
        $v_outlist = null;
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

            switch ($id) {
                    // handle query expression      
                case self::T_GRAPH_MULTI_STRING:
                    $v_description = trim($v, '" ');
                    break;
                case self::T_GRAPH_STRING:
                    /* out of context else string declaration - constant var=""*/
                    $v_description = trim($v, '" ');
                    break;
                case self::T_GRAPH_START:
                    $def_name = false;
                    if (is_null($o)) {
                        // first object
                        $o = [];
                        $start = 1;
                    } else {
                        igk_debug_wln("add - parent ");
                        if (!empty($f_info->name)) {
                            $n = trim($f_info->name);
                            $o[$n] = [];
                            $q[] = &$o;
                            $o = &$o[$n];
                            $f_info = $parser->_create_info();
                        } else {
                            igk_die("no name defined - " . $o);
                        }
                        $start++;
                    }
                    break;
                case self::T_GRAPH_END:
                    if (!empty($n = $f_info->name)) {
                        $this->_update_mark($o, $f_info, $data, $v_description);
                    }
                    $mvv = $p;
                    if (($data instanceof GraphQlData) && $this->m_listener && !$v_ldinput && !$v_init_data) {
                        // load entry fields with listener - no data to init
                        if (empty(array_filter(array_values((array)$o)))) { 
                            $t_entry = $this->m_listener->query();
                            $data->storeEntry($t_entry);
                            if ($t_entry  && !$data->isIndex()) {
                                // update query entries 
                                foreach (array_keys($o) as $k) {
                                    $o[$k] = $data->getValue($k, $t_entry, $this->_get_model_callback());      
                                }
                            }
                        }
                    }

                    if ($data->isIndex()) {
                        // o is the modele
                        $other = array_slice($data->entry, 1);
                        // copy data
                        $tab = [$o];
                        $model = array_keys($o);

                        while (count($other) > 0) {
                            $rp = array_shift($other);
                            $i = [];
                            foreach ($model as $k) {
                                $i[$k] = $data->getValue($k, $rp, $this->_get_model_callback());
                            }
                            $tab[] = $i;
                        }
                        // + | change the reference of  
                        $o = $tab;
                        $data->storeEntry(-1);
                    }


                    if (($c = count($q)) > 0) {
                        $o = &$q[$c - 1];
                        array_pop($q);
                    }

                    if (count($p) > 0) {
                        $data = array_pop($p);
                    }
                    $start--;
                    if ($start === 0) {
                        $start = false;
                    }
                    if ($v_ldinput != null) {
                        $v_ldinput->definition = $data->getInfo();
                        // update root query request 
                        if (!$start && ($v_ldinput->type == 'query')) {
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
                    if ($start) {
                        if (!empty($f_info->name)) {
                            if ($v_alias) {
                                // move alias to target
                                $f_info->alias = $f_info->name;
                                $f_info->name = $v;
                                $v_alias = false;
                                break;
                            }
                            $this->_update_mark($o, $f_info, $data, $v_description);
                        } else {
                            $f_info->description = $v_description;
                        }
                        $f_info->name = $v;
                        $v_description = null;
                    } else {
                        if ($v_ldinput && $def_name) {
                            $v_ldinput->name = $v;
                            $def_name = false;
                        } else if ($tn = $this->_get_token($v)) {
                            if ($tn === self::T_GRAPH_DECLARE_INPUT) {
                                $v_pinput = $v_ldinput;
                                $v_ldinput = GraphQlDeclaredInputFactory::Create($v)  ?? igk_die('missing declared input name [' . $v . ']');
                                $v_declaredInputs[$v] = $v_ldinput;
                                $def_name = true;
                                $v_ldinput->parent = $v_pinput;
                                if ($v_description) {
                                    $v_ldinput->description = $v_description;
                                }
                                $v_description = null;
                            }
                        }
                    }
                    break;
                case self::T_GRAPH_TYPE_DEFINITION:
                    $f_info->type = $v["type"];
                    $f_info->default = $v["default"];
                    $this->_update_mark($o, $f_info, $data, $v_description);
                    $v_description = null;
                    break;
                case self::T_GRAPH_ARGUMENT:
                    // Agument for reading info
                    if ($v_ldinput) {

                        $v_n = $v_ldinput->name;
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
                            $params = $this->_getMethodParameter($v, $v_type);
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
                    }
                    break;
            }
        }
        if (!empty($f_info->name)) {
            $this->_update_mark($o, $f_info, $data, $v_description);
        }
        $this->m_declaredInputs = $v_declaredInputs;
        if ($v_alias) {
            $v_alias = false;
        }
        if ($v_outlist) {
            $o = $v_outlist;
        }
    }
    private function _getMethodParameter($params, $type = 'query')
    {
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
                }
            }
            $tab[] = $d;
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
    protected function _update_mark(&$o, &$f_info, $data, ?string &$v_description = null)
    {
        $n = $f_info->name;
        if (empty($n)) {
            igk_die("name is empty");
        }
        $v_data = $data ? $data->first() : null;
        $data->storeInfo($n, $f_info);
        $v = $f_info->default;
        if (!is_null($v_data)) {
            $v = $data->getValue($f_info->name, $v_data, $this->_get_model_callback());

            // if ($v_data instanceof IGraphQlMappingData){
            //     $v = $v_data->getMappingValue($n, $f_info->default);
            // }
            // else if ($v_data instanceof ModelBase){
            //     $info = $v_data->getTableInfo();
            //     $tn = sysutil::GetModelTypeNameFromInfo($info);
            //     $map_data = $this->getMapData($tn);
            //     if ($map_data){
            //         $b = $v_data->map($map_data);
            //         $v = igk_getv($b, $n,  $f_info->default);
            //     }
            // }
            // else{
            //     $v = igk_getv($v_data, $n, $f_info->default);
            // }
        }
        $o[$n] = $v;
        $f_info = $this->_create_info();
        $f_info->description = $v_description;
        $v_description = null;
        return $n;
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
        $o = $r->read($l);
        foreach ($o as $k => $v) {
            $p = explode('=', $v, 2);
            if (count($p) >= 2) {
                $d = trim($p[1]);
                $t = trim($p[0]);
                if (is_numeric($d)) {
                    $d = floatval($d);
                }
                $o->$k = ['default' => $d, 'type' => $t, 'directive' => null];
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
                    if (($v_spead = $ch . substr($this->m_text, $pos, 2)) == '...') {
                        $this->m_token = [self::T_GRAPH_SPREAD_OPERATOR, $v_spead];
                        $pos += 3;
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
                    if ($this->_handle_name($l, $pos))
                        return true;
                    $this->m_token = [self::T_GRAPH_START, $ch];
                    return true;
                case '}':
                    if (!empty($l)) {
                        $pos--;
                        $this->m_token = [self::T_GRAPH_NAME, trim($l)];
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
                case '(':
                    if ($this->_handle_name($l, $pos))
                        return true;
                    $this->m_token = $ch;
                    $this->m_readMode = self::READ_ARGUMENT;
                    return true;
                case ')':
                    $this->m_token = $ch;
                    $this->m_readMode = self::READ_END_ARGUMENT;
                    return true;
                default:
                    $ip = strpos(self::LITTERAL_TOKEN, $ch);
                    if ($ip !== false) {
                        $l .= $ch;
                    } else {
                        if ($this->m_readMode == self::READ_NAME) {
                            $n = trim($l);
                            if (strpos($n, "__") === 0) {
                                $this->m_token = [self::T_GRAPH_INTROPECTION, $n];
                                return true;
                            }
                            $token = $this->_get_token($n) ?? self::T_GRAPH_NAME;
                            $this->m_token = [$token, $n];
                            return true;
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
}
