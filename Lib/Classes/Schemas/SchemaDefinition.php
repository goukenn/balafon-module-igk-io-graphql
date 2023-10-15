<?php
// @author: C.A.D. BONDJE DOUE
// @file: SchemaDefinition.php
// @date: 20231006 23:26:01
namespace igk\io\GraphQl\Schemas;


///<summary></summary>
/**
* global definition 
* @package igk\io\GraphQl\Schemas
*/
class SchemaDefinition{
    var $queryType;
    var $mutationType;
    var $subscriptionType;
    var $types = [];
    var $directives = [];

    public function __construct()
    {
        $this->queryType = new SchemaQueryTypeDefinition();
        $this->queryType->name = "Query";
    }
}