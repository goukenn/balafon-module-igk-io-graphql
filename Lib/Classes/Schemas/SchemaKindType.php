<?php
// @author: C.A.D. BONDJE DOUE
// @file: SchemaKindType.php
// @date: 20231006 23:28:47
namespace igk\io\GraphQl\Schemas;


///<summary></summary>
/**
* 
* @package igk\io\GraphQl\Schemas
*/
abstract class SchemaKindType{
    const OBJECT = 'OBJECT';
    const SCALAR = 'SCALAR';
    const LIST = 'LIST'; // for array 
    const NON_NULL = 'NON_NULL';
}