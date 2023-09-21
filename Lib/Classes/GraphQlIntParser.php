<?php
// @author: C.A.D. BONDJE DOUE
// @file: GraphQlIntParser.php
// @date: 20221105 17:25:56
namespace igk\io\GraphQl;


///<summary></summary>
/**
* 
* @package IGK\igk\io\GraphQl
*/
class GraphQlIntParser{
    public function parse($data){
        if (is_numeric($data))
            return intval($data);
        return 0;
    }
}