<?php
// @author: C.A.D. BONDJE DOUE
// @file: GraphQlDeclaredType.php
// @date: 20230926 09:16:41
namespace igk\io\GraphQl;


///<summary></summary>
/**
* 
* @package igk\io\GraphQl
*/
class GraphQlDeclaredType{
    /**
     * type name
     * @var mixed
     */
    var $name;
    /**
     * type description
     * @var mixed
     */
    var $description;

    var $type = 'type';

    var $parent = null;
}