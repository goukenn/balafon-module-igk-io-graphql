<?php
// @author: C.A.D. BONDJE DOUE
// @file: GraphQlFloatParser.php
// @date: 20221105 17:26:50
namespace igk\io\GraphQl;


///<summary></summary>
/**
* 
* @package IGK\igk\io\GraphQl
*/
class GraphQlFloatParser{
    public function parse($data){
        if (is_numeric($data))
            return floatval($data);
        return 0;
    } 
}