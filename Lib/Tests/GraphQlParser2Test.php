<?php
// @author: C.A.D. BONDJE DOUE
// @file: GraphQlParser2Test.php
// @date: 20231009 16:35:32
namespace igk\io\GraphQl\Tests;

use igk\io\GraphQl\GraphQlExportArgument;
use igk\io\GraphQl\GraphQlParser;
use igk\io\GraphQl\GraphQlSyntaxException;
use IGK\System\Exceptions\ArgumentTypeNotValidException;
use IGK\Tests\Controllers\ModuleBaseTestCase;
use IGKException;
use ReflectionException;
use SebastianBergmann\RecursionContext\InvalidArgumentException;
use PHPUnit\Framework\ExpectationFailedException;

///<summary></summary>
/**
* 
* @package igk\io\GraphQl
*/
class GraphQlParser2Test extends ModuleBaseTestCase{
    private function _lib($name){
        return file_get_contents(__DIR__."/Data/{$name}.gql");

    }
    public function test_missing_property_query(){
        $this->expectException(GraphQlSyntaxException::class);
        GraphQlParser::Parse($this->_lib(__FUNCTION__)); 
    }
    // /**
    //  * 
    //  * @return void 
    //  * @throws IGKException 
    //  * @throws GraphQlSyntaxException 
    //  * @throws ArgumentTypeNotValidException 
    //  * @throws ReflectionException 
    //  * @throws InvalidArgumentException 
    //  * @throws ExpectationFailedException 
    //  */
    // public function test_global_query(){ 
    //     $o = GraphQlParser::Parse($this->_lib(__FUNCTION__)); 
    //     $o =  json_encode($o);
        
    //     $this->assertEquals(
    //         json_encode((object)['name'=>null, 'firstname'=>null]),
    //         $o);
    // }
    // public function test_global_query_list(){ 
    //     $o = GraphQlParser::Parse($this->_lib(__FUNCTION__)); 
    //     $o = json_encode($o);
        
    //     $this->assertEquals(
    //         json_encode((object)['name'=>["fullname"=>null]]),
    //         $o);
    // }
    // public function test_global_func(){ 
    //     $o = GraphQlParser::Parse($this->_lib(__FUNCTION__)); 
    //     $this->assertEquals(
    //         json_encode((object)['name'=>['title'=>null]]),
    //         json_encode($o));
    // }
    // private function _skipOption(){
    //     return [
    //         'noSkipFirstNamedQueryEntry'=>true
    //     ];
    // }
    // public function test_no_skip_firstname_entry(){ 
    //     $o = GraphQlParser::Parse($this->_lib(__FUNCTION__)); 
    //     $o =  json_encode($o);
        
    //     $this->assertEquals(
    //         json_encode((object)['userList'=>['name'=>null, 'lastname'=>null]]),
    //         $o);
    // }
    // public function test_global_simple_directive(){ 
    //     $o = GraphQlParser::ParseWithOption($this->_skipOption(), $this->_lib(__FUNCTION__)); 
    //     $this->assertEquals(
    //     json_encode((object)['userList'=>['name'=>null]]),
    //     json_encode($o));

    //     $o = GraphQlParser::Parse($this->_lib(__FUNCTION__)); 
    //     $this->assertEquals(
    //         json_encode((object)['name'=>null]),
    //         json_encode($o));
    // }
    // public function test_global_simple_directive_after(){ 
    //     $o = GraphQlParser::Parse($this->_lib(__FUNCTION__),null, $reader, null, $this->_skipOption()); 
    //     $this->assertEquals(
    //         json_encode((object)['userList'=>['name'=>null]]),
    //         json_encode($o));
    // }

    // public function test_export_argument(){
    //     $exp = new GraphQlExportArgument;
    //     $o = $exp->export("limit:12, x:8");
    //     $this->assertEquals((object)['limit'=>12, 'x'=>8], $o);
    // }
    // public function test_export_with_variable(){
    //     $exp = new GraphQlExportArgument;
    //     $exp->variables = ['local'=>8];
    //     $o = $exp->export("limit:12, x:\$local");
    //     $this->assertEquals((object)['limit'=>12, 'x'=>8], $o);
    // }
    // public function test_export_declare_with_variable(){
    //     $this->expectException(GraphQlSyntaxException::class);
    //     $exp = new GraphQlExportArgument;
    //     $exp->variables = ['local'=>8];
    //     $o = $exp->export("\limit: int = 12");
    // }

    // public function test_export_declare_with_variable_correct(){
    //     $exp = new GraphQlExportArgument;
    //     $exp->variables = ['local'=>8];
    //     $o = $exp->export("\$limit: int = 12");
    //     $this->assertEquals('{"$limit":{"name":"limit","directive":null,"type":"int","default":12}}', json_encode($o));
    // }

    // public function test_global_with_default(){ 
    //     $o = GraphQlParser::Parse($this->_lib(__FUNCTION__),[
    //         "name"=>"C.A.D BONDJE", 
    //         'email'=>'cbondje@igkdev.com'
    //     ]); 
    //     $b = json_encode($o);
    //     $this->assertEquals(
    //         json_encode((object)[
    //             'DB'=>['name'=>"C.A.D BONDJE"], 
    //              'Info'=>['email'=>'cbondje@igkdev.com', 'name'=>'C.A.D BONDJE']
    //         ]),
    //         $b);
    // }

    // public function test_global_with_to_query(){ 
    //     $o = GraphQlParser::Parse($this->_lib(__FUNCTION__),[
    //         ["name"=>"C.A.D BONDJE", 
    //         'email'=>'cbondje@igkdev.com']
    //     ]); 
    //     $b = json_encode($o);
    //     $this->assertEquals(
    //         json_encode(['Query'=>[
    //             ['name'=>"C.A.D BONDJE",
    //             'email'=>'cbondje@igkdev.com']
    //         ]]),
    //         $b);
    // }


    // public function test_invoke_query_method_from_listener(){ 
    //     $o = GraphQlParser::Parse($this->_lib(__FUNCTION__),new GraphQlParser2Listener); 
    //     $this->assertTrue(
    //         is_array($o)
    //     );
    //     $this->assertTrue(
    //         count($o)==19
    //     );
    // }
    // public function test_invoke_query_method_from_listener_missing_props(){ 
    //     $this->expectException(GraphQlSyntaxException::class);
    //     $o = GraphQlParser::Parse($this->_lib(__FUNCTION__),new GraphQlParser2Listener); 
    // }


    // public function test_invoke_method_from_listener(){  
    //     $o = GraphQlParser::Parse($this->_lib(__FUNCTION__),new GraphQlParser2Listener); 
    //     $this->assertEquals('{"users":[{"name":"C.A.D","login":"cbondje@igkdev.com"},{"name":"CHARLES","login":"bondje.doue@igkdev.com"}]}', 
    //     json_encode($o));
    // }
    // public function test_invoke_method_from_listener_details(){  
    //     $o = GraphQlParser::Parse('{users: userDetails(id:1, limit:100){name}}',
    //         new GraphQlParser2Listener); 
    //     $this->assertEquals('{"users":{"name":"CHARLES"}}', 
    //     json_encode($o));
    // }

    // public function test_invoke_with_variables(){  
    //     $o = GraphQlParser::Parse(['query'=>'{users: userDetails(id:$key, limit:100){name}}', 'variables'=>[
    //         'key'=>1
    //     ]],new GraphQlParser2Listener); 
    //     $this->assertEquals('{"users":{"name":"CHARLES"}}', 
    //     json_encode($o));
    // }
    // public function test_invoke_inject_options(){  
    //     $o = GraphQlParser::Parse(['query'=>'{users: selectUser(id:$key, limit:100){name}}', 'variables'=>[
    //         'key'=>1
    //     ]],new GraphQlParser2Listener); 
    //     $this->assertEquals('{"users":{"name":"CHARLES"}}', 
    //     json_encode($o));
    // }


    // /**
    //  * test graph ql fragment
    //  * @return void 
    //  * @throws IGKException 
    //  * @throws GraphQlSyntaxException 
    //  * @throws ArgumentTypeNotValidException 
    //  * @throws ReflectionException 
    //  * @throws InvalidArgumentException 
    //  * @throws ExpectationFailedException 
    //  */
    // public function test_graph_with_fragment(){  
    //     $o = GraphQlParser::Parse(['query'=>'{fragmentUsers{login ...address}} fragment address on User{ street number box }', 'variables'=>[
    //         'key'=>1
    //     ]], new GraphQlFragmentTestListener('User')); 
    //     $this->assertEquals('{"fragmentUsers":[{"login":"cbondje@igkdev.com","street":"A","number":10,"box":"J"},{"login":"bondje.doue@igkdev.com","street":"B","number":7,"box":"G"}]}', 
    //     json_encode($o));
    // }
 
}