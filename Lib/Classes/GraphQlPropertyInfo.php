<?php
// @author: C.A.D. BONDJE DOUE
// @file: GraphQlPropertyInfo.php
// @date: 20231009 20:03:20
namespace igk\io\GraphQl;


///<summary></summary>
/**
* 
* @package igk\io\GraphQl
*/
class GraphQlPropertyInfo extends GraphQlDocProperty{
    var $name;
    var $alias;
    /**
     * property type 
     * @var mixed query|func
     */
    var $type;
    /**
     * array of directives
     * @var ?array
     */
    var $directives; 

    /**
     * access 
     * @var ?string 
     */
    var $path;

    /**
     * parent section
     * @var mixed
     */
    var $section;

    /**
     * is a child property section 
     * @var ?bool
     */
    var $child;

    /**
     * the default property value
     * @var mixed
     */
    var $default;

    /**
     * argument for function definition
     * @var mixed
     */
    var $args;
   
    public function __construct(string $name)
    {
        $this->name = $name;
        $this->directives = [];
        $this->child = false;
        $this->type = 'query';
    }
    public function createPropertyResolutionValue($data){ 
        return null;
    }
    /**
     * get property key
     * @return mixed 
     */
    public function getKey(){
        return $this->alias ?? $this->name;
    }
}