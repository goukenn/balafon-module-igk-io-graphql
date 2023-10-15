<?php
// @author: C.A.D. BONDJE DOUE
// @file: GraphQlDefinitionBuilder.php
// @date: 20230921 16:57:05
namespace igk\io\GraphQl\Schemas;

use IGK\System\IO\StringBuilder;

///<summary></summary>
/**
* 
* @package igk\io\GraphQl\Schemas
*/
class GraphQlDefinitionBuilder
{
    private $m_types = [];
    private $m_enums = [];
    private $m_query = [];

    /**
     * field definition 
     * @param string $type 
     * @param array $definition 
     * @return void 
     */
    public function addType(string $type, array $definition)
    {
        // if ($type == 'Query') {
        //     igk_die("Query is a reserved typed");
        // }
        $this->m_types[$type] = $definition;
    }

    public function argDefinition($arg){

        $sb = new StringBuilder;
        $ch = '';
        $t = $d = null;
        foreach($arg as $k=>$v){
            $d = null;
            if (is_array($v)){
                $t = array_shift($v) ?? 'String';
                $d = array_shift($v);
            }   else {
                $t = $v;
            }

            $sb->append($ch.$k.':'.$t);
            if ($d){
                $sb->append(' = '.$d);
            }
            $ch=',';
        }
        return $sb.'';
    }
    /**
     * render definition
     */
    public function render(): string
    {
        $sb = new StringBuilder;

        foreach ($this->m_types as $t => $def) {
            $s = 'type ' . $t . '{' . PHP_EOL;
            foreach ($def as $key => $def) {
                # code...
                $s .= "\t" . $def->name;
                if ($def->args){
                    $s.= sprintf('(%s)', $this->argDefinition($def->args));
                }
                if ($def->type) {
                    $ts = '';
                    $ts .= $def->type;
                    if ($def->isRequired) {
                        $ts .= '!';
                    }
                    if ($def->isArray) {
                        $ts = '[' . $ts . ']';
                    }
                    $s .= ":" . $ts;
                    if ($def->default) {
                        $s .= " = " . $def->default;
                    }
                }

                $s .= PHP_EOL;
            }


            $s .= '}' . PHP_EOL;

            $sb->append(sprintf('%s', $s));
        }

        if ($query = $this->_renderEnum())
            $sb->appendLine($query);

        if ($query = $this->_renderQuery())
            $sb->appendLine($query);

        return '' . $sb;
    }
    /**
     * 
     * @return null|string 
     */
    protected function _renderQuery(): ?string
    {
        if (!$this->m_query) {
            return null;
        }

        $s = 'type Query{' . PHP_EOL;
        foreach ($this->m_query as $qdef) {
            $s.= $qdef;
        }
        $s .= '}';
        return $s;
    }
    /**
     * 
     * @return null|string 
     */
    protected function _renderEnum(): ?string
    {
        if (!$this->m_enums) {
            return null;
        }
        foreach ($this->m_enums as $n=>$qdef) {
            $s = 'enum '.$n.'{' . PHP_EOL;

            $s .= '}'.PHP_EOL;
        }
        return $s;
    }
}
