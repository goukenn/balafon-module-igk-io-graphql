<?php
// @author: C.A.D. BONDJE DOUE
// @file: IGraphQlUpdateField.php
// @date: 20231009 12:44:29
namespace igk\io\GraphQl;


///<summary></summary>
/**
* 
* @package igk\io\GraphQl
*/
interface IGraphQlUpdateField{
    function updateFields($reader, $key, & $obj, $data, $t_entry, $callback);
}