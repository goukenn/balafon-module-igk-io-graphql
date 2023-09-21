<?php
// @author: C.A.D. BONDJE DOUE
// @file: IGraphQlMapDataResolver.php
// @date: 20230921 22:54:30
namespace igk\io\GraphQl;


///<summary></summary>
/**
* 
* @package igk\io\GraphQl
*/
interface IGraphQlMapDataResolver{
    /**
     * query type name
     * @param string $typeName 
     * @return mixed 
     */
    function getMapData(string $typeName);
}