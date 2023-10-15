<?php
// @author: C.A.D. BONDJE DOUE
// @file: Mutation.php
// @date: 20231015 16:37:25
namespace igk\io\GraphQl\Annotations;



///<summary></summary>
/**
* 
* @package igk\io\GraphQl\Annotations
*/
class Mutation extends AnnotationBase{
    /**
     * set the alias name
     * @var mixed
     */
    var $name;
    /**
     * set the return type
     * @var mixed
     */
    var $returnType;

    public function __construct()
    {
        
    }
}