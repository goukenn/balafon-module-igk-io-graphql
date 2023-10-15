<?php
// @author: C.A.D. BONDJE DOUE
// @file: IGraphQlInspector.php
// @date: 20231006 23:42:35
namespace igk\io\GraphQl;


///<summary></summary>
/**
* 
* @package igk\io\GraphQl
*/
interface IGraphQlInspector{
    /**
     * retrieve source typename
     * @return null|string 
     */
    function getSourceTypeName(): ?string;
    function query();
}