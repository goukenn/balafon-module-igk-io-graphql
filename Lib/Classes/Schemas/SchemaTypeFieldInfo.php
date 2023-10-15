<?php
// @author: C.A.D. BONDJE DOUE
// @file: SchemaTypeFieldInfo.php
// @date: 20231006 23:26:46
namespace igk\io\GraphQl\Schemas;


///<summary></summary>
/**
* 
* @package igk\io\GraphQl\Schemas
*/
class SchemaTypeFieldInfo{
    var $name;
    var $description;
    var $alias;
    var $args; // <- field args is a rquired field
    var $type; // <- SchemaTypeFieldTypeDefinition Info
    var $isDeprecated;
    var $deprecationReason;

    public function setAlias(?string $a){
        $this->alias = $a;
        return $this;
    }
    public function __construct()
    {
        $this->type = new SchemaTypeFieldTypeDefinition;
        $this->args = new SchemaTypeFieldArgDefinition;
        $this->scalar('String');
    }
    /**
     * define type is a list of type 
     * @param string $typeName 
     * @return void 
     */
    public function listOf(string $typeName){
        $this->type->kind = SchemaKindType::LIST;
        $this->type->name = null;
        $this->type->ofType = (object)[
            "kind"=>SchemaKindType::OBJECT,
            "name"=>$typeName
        ];
    }
    /**
     * define type is a non null scalar type 
     * @param string $scalarType 
     * @return void 
     */
    public function nonNullScalar(string $scalarType){
        $this->type->kind = SchemaKindType::NON_NULL;
        $this->type->name = null;
        $this->type->ofType = (object)[
            "kind"=>SchemaKindType::SCALAR,
            "name"=>$scalarType
        ];
    }
    public function scalar(string $scalarType){
        $this->type->kind = SchemaKindType::SCALAR;
        $this->type->name = $scalarType;
        $this->type->ofType = null;
    }
}