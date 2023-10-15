<?php
// @author: C.A.D. BONDJE DOUE
// @file: GraphQlTestBaseTest.php
// @date: 20231013 08:15:48
namespace igk\io\GraphQl\Tests;

use IGK\Tests\Controllers\ModuleBaseTestCase;

///<summary></summary>
/**
* 
* @package igk\io\GraphQl\Tests
*/
abstract class GraphQlTestBase extends ModuleBaseTestCase{
    public static function _lib($name){
        return file_get_contents(__DIR__."/Data/{$name}.gql");

    }
}