<?php
// @author: C.A.D. BONDJE DOUE
// @file: FragmentDeclaredInput.php
// @date: 20230921 17:58:55
namespace igk\io\GraphQl;

use igk\io\GraphQl\Schemas\GraphQLFieldInfo;
use IGKException;
use IGK\System\Exceptions\ArgumentTypeNotValidException;
use ReflectionException;

///<summary></summary>
/**
* 
* @package igk\io\GraphQl
*/
class FragmentDeclaredInput extends GraphQlDeclaredInput{
    /**
     * 
     * @var mixed
     */
    var $on;

    /**
     * list of definition fields
     * @var array
     */
    private $m_fields = [];


    /**
     * override definition 
     * @param mixed $reader 
     * @return true 
     * @throws GraphQlSyntaxException 
     * @throws IGKException 
     * @throws ArgumentTypeNotValidException 
     * @throws ReflectionException 
     */
    public function readDefinition($reader):bool
    {
        $v_level = 0;
        $v_end = false; 
        $v_readon = false;
        if(is_null($this->name)){
            
            if (is_null($this->name = $reader->readName())){
                throw new GraphQlSyntaxException('missing fragment name');
            } 
        }
        $v_fields = & $this->m_fields;
        $p = null;
        $v_description = null;
        $field = null;
        while(!$v_end && $reader->read()){
            $e = $reader->token(); // s->m_token;
            if (! $v_readon && ($e[1]=='on')){ 
                $v_readon = true;
                if (is_null($v_type = $reader->readName() )){
                    throw new GraphQlSyntaxException('missing on type');
                }
                $this->on = $v_type;
                continue;
            }
            if ($e == '('){
                if ($reader->read() && $field){
                    $e = $reader->getToken();
                    $field->type = 'method';
                    $field->args = $e[1];

                }
                continue;
            }

            switch($e[0]){
                
                case GraphQlReaderConstants::T_READ_END:
                    $v_level--;
                    if ($v_level===0){
                        $v_end = true;
                    }
                    if (is_array($p)){
                        if ($v_parent = array_shift($p)){
                            $v_parent->parent[] = $v_fields;
                            $v_fields = & $v_parent->parent;
                        }
                    }                    
                    break;
                case GraphQlReaderConstants::T_READ_START:
                    if ($p===null){
                        $p = [];
                    }else{
                        // connect to reference
                        $ref = (object)['parent'=>& $v_fields, 'def'=>[]];
  
                        array_unshift($p, $ref);
 
                        $v_fields = & $ref->def;
                        // add flag 
                        //$v_fields[] = '--input--';
                    }
                    $v_level++;
                    break;
                case GraphQlReaderConstants::T_READ_NAME:
                    if ($v_level>0){
                        $field = new GraphQLFieldInfo;
                        $n = $e[1];
                        if (empty($n)){
                            throw new GraphQlSyntaxException("name is empty");
                            break;
                        }
                        $field->name = $n;
                        $field->description = $v_description;
                        $v_fields[$n] = $field; 
                        $v_description = null;
                    }
                    break;
                default:
                    igk_debug_wln_e("the data read " , $e);
                break;
            }
        }
        return true;
    }
    public function getFieldKeys(){
        return array_keys($this->m_fields);
    }
    public function getFields(){
        return $this->m_fields;
    }

    public function onType(GraphQlReadSectionInfo $section):bool{
        empty($this->on) && igk_die("missing on declaration"); 
       return $section->getSourceTypeName() == $this->on; 
    }
}