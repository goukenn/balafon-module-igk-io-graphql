<?php
// @author: C.A.D. BONDJE DOUE
// @file: SchemaTypeDefinition.php
// @date: 20231006 23:27:06
namespace igk\io\GraphQl\Schemas;


///<summary></summary>
/**
* 
* @package igk\io\GraphQl\Schemas
*/
class SchemaTypeDefinition{
    
    var $kind= "OBJECT"; // <- OBJECT|Scalar
    var $name;
    var $description;
    var $fields= [];       
    var $inputFields ; 
    var $interfaces ; // <- require for kind OBJECT and it must be an array 
    var $enumValues ; 
    var $possibleTypes ; 

    private static $sm_create_scalar;
    public static function CreateScalar(string $name, ?string $description=null){
        if (is_null(self::$sm_create_scalar)){
            self::$sm_create_scalar = [];
        }
        if (!isset(self::$sm_create_scalar[$name])){
            $n = new self;
            $n->name = $name;
            $n->description = $description;
            $n->kind = SchemaKindType::SCALAR;
            self::$sm_create_scalar[$name] = $n;
        }        
        return self::$sm_create_scalar[$name];
    }
    public static function CreateObject(string $name){
        $n = new self;
        $n->name = $name;
        $n->kind = SchemaKindType::OBJECT;
        $n->interfaces = new SchemaInterfaceList;
        return $n;
    }
    /** add field by name */
    public function addField(string $type_name):SchemaTypeFieldInfo {
        $f= new SchemaTypeFieldInfo;
        $f->name = $type_name;
        $this->fields[] = $f;
        return $f;
    }
}