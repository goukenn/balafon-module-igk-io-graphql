<?php
// @author: C.A.D. BONDJE DOUE
// @file: GraphQlReaderConstansts.php
// @date: 20231009 16:44:55
namespace igk\io\GraphQl;


///<summary></summary>
/**
* 
* @package igk\io\GraphQl
*/
class GraphQlReaderConstants{
    const T_TOKEN='_abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    const T_SPREAD_OPERATOR = '...';
    const T_LITTERAL_DECLARATION='query|mutation|directive';
    const T_RESERVED_PROPERTIES = '__type';

    
    const T_READ_NAME=1;
    const T_READ_START = 2;
    const T_READ_END = 3; 
    const T_READ_COMMENT = 4;
    const T_READ_DESCRIPTION = 5;
    const T_READ_FUNC_ARGS = 6;
    const T_READ_ALIAS=7;
    const T_READ_END_FUNC_ARGS = 8;
    const T_READ_END_FIELD = 9;
    const T_READ_DIRECTIVE = 10;

    const T_READ_ARGUMENT = 11;
    const T_READ_FRAGMENT  = 12;
    const T_READ_SPEAR = 13;
    const T_READ_INLINE_SPEAR = 14;
    // extra default value for query inline definition - not in specification for query read
    const T_READ_DEFAULT_VALUE = 15;

    const RESERVED_WORD = 'true|false|null|schema|query|type|enum|mutation';
    const SPEAR_OPERATOR = '...';

}