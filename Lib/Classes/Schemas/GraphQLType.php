<?php
// @author: C.A.D. BONDJE DOUE
// @file: GraphQLType.php
// @date: 20230921 16:58:17
namespace igk\io\GraphQl\Schemas;


///<summary></summary>
/**
* 
* @package igk\io\GraphQl\Schemas
*/
class GraphQLType
{
    const TYPES = 'ID|Boolean|Int|Float|String';
    public static function GetTypeFromDbColumnInfo($info)
    {
        if ($info->clType == 'Int') {
            if ($info->clIsPrimary && $info->clAutoIncrement) {
                return 'ID';
            }
            return 'Int';
        }
        return 'String';
    }
}