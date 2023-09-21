<?php
// @author: C.A.D. BONDJE DOUE
// @file: QueryDeclaredInput.php
// @date: 20230921 17:28:44
namespace igk\io\GraphQl;


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

    public function setParameter($paralist, $params=null){
        $d = [];
        foreach($paralist as $k=>$v){
            if ($k[0]=="$"){
                $d[substr($k,1)] = "";
            }
        }
        $this->parameters = $d;
    }
}