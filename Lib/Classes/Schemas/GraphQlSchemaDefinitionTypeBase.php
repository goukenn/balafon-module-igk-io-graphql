<?php
// @author: C.A.D. BONDJE DOUE
// @file: GraphQlSchemaDefinitionTypeBase.php
// @date: 20231015 18:22:26
namespace igk\io\GraphQl\Schemas;


///<summary></summary>
/**
* 
* @package igk\io\GraphQl\Schemas
*/
abstract class GraphQlSchemaDefinitionTypeBase{
    public abstract function buildSchema(ServeSchema $schema);
}