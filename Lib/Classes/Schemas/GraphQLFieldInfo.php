<?php
// @author: C.A.D. BONDJE DOUE
// @file: GraphQLFieldInfo.php
// @date: 20230921 16:57:42
namespace igk\io\GraphQl\Schemas;


///<summary></summary>
/**
* 
* @package igk\io\GraphQl\Schemas
*/
class GraphQLFieldInfo
{
    /**
     * name of the field 
     * @var mixed
     */
    var $name;
    /**
     * default type
     * @var mixed
     */
    var $type;
    /**
     * 
     * @var mixed
     */
    var $isRequired;
    /**
     * 
     * @var false
     */
    var $isArray = false;
    /**
     * default value
     * @var null|string|int 
     */
    var $default;

    /**
     * argument for method type
     * @var ?object
     */
    var $args;


    /**
     * field description
     * @var ?string
     */
    var $description;
}