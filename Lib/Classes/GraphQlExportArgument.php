<?php
// @author: C.A.D. BONDJE DOUE
// @file: GraphQlExportArgument.php
// @date: 20231010 08:34:00
namespace igk\io\GraphQl;

use IGK\Helper\Activator;
use IGK\System\IO\Configuration\ConfigurationReader;

///<summary></summary>
/**
* 
* @package igk\io\GraphQl
*/
class GraphQlExportArgument{
    var $variables;
    public function export(string $data){
        $r = new ConfigurationReader;
        $r->delimiter = ',';
        $r->separator = ':';
        // with bracket treat argument expression
        $r->escape_start= '[';
        $r->escape_end= ']'; 
        // + | transform inline array definition to expression of json - decoding
        $l = $r->treatExpression($data, $expression);
     
        $o = $r->read($l);
        foreach ($o as $k => $v) {
            if (is_null($v)){
                continue;
            }
            if (strpos($v, '$')===0){
                // support variable
                $v = substr($v,1);
                if ($this->variables && isset($this->variables[$v])){
                    $o->$k = igk_getv($this->variables, $v);
                }
                continue;                
            }
            $p = explode('=', $v, 2);
            if (count($p) >= 2) {
                if (strpos($k,  '$') !== 0){
                    throw new GraphQlSyntaxException('not a variable argument declaration');
                }

                $d = trim($p[1]);
                $t = trim($p[0]);
                if (is_numeric($d)) {
                    $d = floatval($d);
                }
                $o->$k = Activator::CreateNewInstance(GraphQlDeclareArgVariable::class, ['name'=>substr($k, 1), 'default' => $d, 'type' => $t, 'directive' => null]);
            }
            if ($expression && key_exists($v, $expression)){
                $o->$k = json_decode($expression[$v]);
            }
        }
        return $o;
    }
}