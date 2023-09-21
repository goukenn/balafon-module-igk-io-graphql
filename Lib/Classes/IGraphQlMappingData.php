<?php
// @author: C.A.D. BONDJE DOUE
// @file: IGraphQlMappingData.php
// @date: 20230921 22:50:41
namespace igk\io\GraphQl;


///<summary></summary>
/**
* 
* @package igk\io\GraphQl
*/
interface IGraphQlMappingData{
    /**
     * object that can resolve the value
     * @param string $name 
     * @param mixed $default 
     * @return mixed 
     */
    public function getMappingValue(string $name, $default=null);
}