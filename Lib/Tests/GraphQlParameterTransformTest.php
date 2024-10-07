<?php
// @author: C.A.D. BONDJE DOUE
// @file: GraphQlParameterTransformTest.php
// @date: 20231017 09:34:01
namespace igk\io\GraphQl\Tests;

use IGK\Controllers\SysDbController;
use igk\io\GraphQl\GraphQlQueryOptions;
use igk\io\GraphQl\System\Database\Helpers\GraphQlDbHelper;
use IGK\Models\Users;
use IGK\System\Http\Request;
use IGK\Tests\Controllers\ModuleBaseTestCase;
use ReflectionFunction;
use ReflectionMethod;

///<summary></summary>
/**
* 
* @package igk\io\GraphQl\Tests
*/
class GraphQlParameterTransformTest extends ModuleBaseTestCase{
    private function localtest(int $x, Request $request, SysDbController $controller){

    }
    public function test_method_transform(){
        $fc = function (Users $user, int $x, int $y = 4, string $limit="100", GraphQlQueryOptions $option=null){
        };
        $p = new ReflectionFunction($fc);
        $s = GraphQlDbHelper::GenSDLFuncParameter($p->getParameters());
        $this->assertEquals('user:ID, x:Int, y:Int = 4, limit:String = "100"', $s);
    }
    public function test_method_transform_local_test(){
        
        $p = new ReflectionMethod($this, 'localtest');
        $s = GraphQlDbHelper::GenSDLFuncParameter($p->getParameters());
        $this->assertEquals('x:Int', $s);
    }
}