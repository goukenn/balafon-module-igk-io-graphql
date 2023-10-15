<?php
// @author: C.A.D. BONDJE DOUE
// @file: IGraphQlUpdateProperty.php
// @date: 20231015 10:12:29
namespace igk\io\GraphQl;


///<summary></summary>
/**
* 
* @package igk\io\GraphQl
*/
interface IGraphQlUpdateProperty{
    function UpdateRefObject(& $refData, $data, $sourceData);
}