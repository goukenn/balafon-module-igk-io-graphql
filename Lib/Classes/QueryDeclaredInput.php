<?php
// @author: C.A.D. BONDJE DOUE
// @file: QueryDeclaredInput.php
// @date: 20230921 17:28:44
namespace igk\io\GraphQl;

use igk\io\GraphQl\GraphQlReadSectionInfo;

///<summary></summary>
/**
* 
* @package igk\io\GraphQl
*/
class QueryDeclaredInput extends GraphQlDeclaredInput{
    /**
     * parameter
     * @var ?array
     */
    var $parameters;

    /**
     * set top query parameter list 
     * @param mixed $paralist 
     * @param mixed $params 
     * @return void 
     */
    public function setParameter($paralist, $params=null){
        $d = [];
        foreach($paralist as $k=>$v){
            if ($k[0]=="$"){
                $d[substr($k,1)] = "";
            }
        }
        $this->parameters = $d;
    }
    /**
     * retrieve the first property section 
     * @return ?GraphQlReadSectionInfo
     */
    public function getSection(): ?GraphQlReadSectionInfo{
        if ($d = $this->definition)
        {
            return (array_shift($d))->section;
        }
        return null;

    }
}