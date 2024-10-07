<?php
// @author: C.A.D. BONDJE DOUE
// @file: IntropectionSourceTypeNameTrait.php
// @date: 20231016 12:23:24
namespace igk\io\GraphQl\Schemas\Traits;


///<summary></summary>
/**
* 
* @package igk\io\GraphQl\Schemas\Traits
*/
trait IntropectionSourceTypeNameTrait{
    private $m_sourceTypeName;

    /**
     * get source type name
     * @return null|string 
     */
    public function getSourceTypeName():?string{
        return $this->m_sourceTypeName;
    }
    /**
     * set source type name
     * @param null|string $m 
     * @return void 
     */
    public function setSourceTypeName(?string $m){
        $this->m_sourceTypeName = $m;
    }
}