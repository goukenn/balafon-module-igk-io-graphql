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
class GraphQlPropertyInfo extends GraphQlDocProperty implements IGraphQlProperty{
    const TYPE_SPEAR = 'spear';
    const TYPE_FUNC = 'func';
    const TYPE_QUERY = 'query';
    const TYPE_DECLARATION = 'declaration';
    var $name;
    var $alias;
    var $args_expression;

    public function __debugInfo()
    {
        return [];
    }
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
     * as child property section 
     * @var ?bool
     */
    var $hasChild;

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


    /**
     * 
     * @var false
     */
    var $optional = false;

    /**
     * 
     * @var mixed
     */
    private  $m_child_section;
   
    public function getChildSection(){
        return $this->m_child_section;
    }
    public function setChildSection(?GraphQlReadSectionInfo $section){
        $this->m_child_section = $section;
    }
    public function __construct(string $name)
    {
        $this->name = $name;
        $this->directives = [];
        $this->hasChild = false;
        $this->type = 'query';
    }
    /**
     * check for property reserverd 
     * @return bool 
     */
    public function isReservedProperty():bool{
        return in_array($this->name, ['__typename','__schema']);
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

    public function getValue($data){ 
        return igk_getv($data, $this->name, $this->default);
    }
}