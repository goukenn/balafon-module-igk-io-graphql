<?php
// @author: C.A.D. BONDJE DOUE
// @file: GraphQlProjectDbSchemaDefinitionBase.php
// @date: 20231016 10:39:59
namespace igk\io\GraphQl\Schemas;

use IGK\Controllers\BaseController;

///<summary></summary>
/**
* 
* @package igk\io\GraphQl\Schemas
*/
abstract class GraphQlProjectDbSchemaDefinitionBase extends GraphQlSchemaDefinitionTypeBase{
    public function buildSchema(ServeSchema $schema, ?SchemaTypeDefinition $querydef=null, ?BaseController $ctrl=null, string $source_intropector_class=null){
        if ($ctrl && $source_intropector_class){
            $j = new GraphQlProjectDbIntrospection($ctrl, $source_intropector_class);
            $j->buildSchema($schema, $querydef);
            return true;
        }  
        return false;
    }
}