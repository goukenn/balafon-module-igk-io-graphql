<?php
// @author: C.A.D. BONDJE DOUE
// @file: GraphQlConstants.php
// @date: 20231006 01:22:25
namespace igk\io\GraphQl;


///<summary></summary>
/**
* 
* @package igk\io\GraphQl
*/
class GraphQlConstants{
    public const MIME_TYPE = 'application/graphql';
    public const HEADER_CONTENT_TYPE = 'Content-Type: '.self::MIME_TYPE;
    public const SCHEMAS_CLASS_DEF_SUFFIX = 'SchemaDefinition';
}