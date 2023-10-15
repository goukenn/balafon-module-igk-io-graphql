<?php
// @author: C.A.D. BONDJE DOUE
// @file: GraphQlSpreadIndex.php
// @date: 20231011 14:41:20
namespace igk\io\GraphQl;


///<summary></summary>
/**
* store spread index in source map
* @package igk\io\GraphQl
*/
class GraphQlSpreadIndex{
    var $index;
    public function __construct(int $index)
    {
        $this->index = $index;
    }
}