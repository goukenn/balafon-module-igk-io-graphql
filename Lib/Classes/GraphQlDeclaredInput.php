<?php
// @author: C.A.D. BONDJE DOUE
// @file: GraphQlDeclaredInput.php
// @date: 20230921 17:22:02
namespace igk\io\GraphQl;


///<summary></summary>
/**
* 
* @package igk\io\GraphQl
*/
class GraphQlDeclaredInput{
    /**
     * 
     * @var string 
     */
    var $type;
    /**
     * 
     * @var mixed
     */
    var $name;

    /**
     * 
     * @var mixed
     */
    var $parent;

    /**
     * 
     * @var mixed
     */
    var $definition;

    /**
     * field descriptions
     * @var mixed
     */
    var $description;

    protected function __construct(){        
    }

    public function readDefinition($reader): bool{
        return false;
    }
}