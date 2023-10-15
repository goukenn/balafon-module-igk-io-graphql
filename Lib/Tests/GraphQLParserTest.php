<?php
// @author: C.A.D. BONDJE DOUE
// @file: GraphQLParserTest.php
// @date: 20221105 09:30:53
// @cmd: phpunit -c phpunit.xml.dist src/application/Packages/Modules/igk/io/GraphQl/Lib/Tests/GraphQLParserTest.php
namespace igk\io\GraphQl\Tests;

use IGK\Controllers\SysDbController;
use IGK\Helper\Activator;
use IGK\Helper\JSon;
use IGK\Helper\JSonEncodeOption;
use igk\io\GraphQl\GraphQL;
use igk\io\GraphQl\GraphQlException;
use igk\io\GraphQl\GraphQlParser;
use igk\io\GraphQl\GraphQlQueryOptions;
use igk\io\GraphQl\GraphQlSyntaxException;
use igk\io\GraphQl\IGraphQlInspector;
use igk\io\GraphQl\IGraphQlMapDataResolver;
use IGK\System\Exceptions\ArgumentTypeNotValidException;
use IGK\System\Exceptions\EnvironmentArrayException;
use IGK\Tests\Controllers\ModuleBaseTestCase;
use IGKException;
use SebastianBergmann\RecursionContext\InvalidArgumentException;
use PHPUnit\Framework\ExpectationFailedException;
use ReflectionException;

///<summary></summary>
/**
 * 
 * @package IGK
 */
class GraphQlParserTest extends ModuleBaseTestCase
{
    var $controller;
    /**
     * retrieve the module 
     * @return mixed 
     * @throws IGKException 
     * @throws EnvironmentArrayException 
     */
    protected function getModule():?\IGK\Controllers\ApplicationModuleController
    {
        return igk_current_module();
    }
    public function test_parse_object()
    {
        $this->expectException(GraphQlSyntaxException::class);
        $obj = GraphQlParser::Parse("{}");
        //$this->assertIsObject($obj);
    }

//     public function test_parse_object_2()
//     {
//         $obj = GraphQlParser::Parse("{name,firstname,lastname}");
//         $this->assertEquals(
//             json_encode((object)["name" => null, "firstname" => null, "lastname" => null]),
//             json_encode($obj)
//         );
//     }

//     public function test_parse_object_3()
//     {
//         $obj = GraphQlParser::Parse("{name: String = 'charles' }");
//         $this->assertEquals(
//             json_encode((object)["name" => 'charles']),
//             json_encode($obj)
//         );
//     }
//     public function test_parse_object_4()
//     {
//         $obj = GraphQlParser::Parse(
//             <<<'GQL'
// {
//     name
//     firstname
//     lastname
// }
// GQL
//         );
//         $this->assertEquals(
//             json_encode((object)["name" => null, "firstname" => null, "lastname" => null]),
//             json_encode($obj)
//         );
//     }

//     public function test_parse_with_data()
//     {
//         $this->expectException(GraphQlException::class);
//         $obj = GraphQlParser::Parse("{name,firstname,lastname}", [
//             'firstname' => 'C.A.D',
//             'lastname' => 'BONDJE DOUE',
//         ]);
//         // $this->assertEquals(
//         //     json_encode((object)["name" => null, "firstname" => 'C.A.D', "lastname" => 'BONDJE DOUE']),
//         //     json_encode($obj)
//         // );
//     }
//     public function test_parse_fwith_data_2()
//     {

//         $obj = GraphQlParser::Parse("# @={noThrowOnMissingProperty} \n{name,firstname,lastname,age:Int}", [
//             'firstname' => 'C.A.D',
//             'lastname' => 'BONDJE DOUE',
//         ]);
//         $this->assertEquals(
//             json_encode((object)["name" => null, "firstname" => 'C.A.D', "lastname" => 'BONDJE DOUE', 'age' => null]),
//             json_encode($obj)
//         );
//     }

//     public function test_parse_listener()
//     {
//         // missing listener to invoke 'user' method
//         $this->expectException(GraphQlException::class);
//         $obj = GraphQlParser::Parse("{user (id:1){ name } lastname }", [
//             'firstname' => 'C.A.D',
//             'lastname' => 'BONDJE DOUE',
//         ]);
//         $this->assertEquals(
//             json_encode((object)["user" => ["name" => "user1"], "lastname" => 'BONDJE DOUE']),
//             json_encode($obj)
//         );
//     }

//     static function CreateGraphListener(string $class_name, $source = null){
//         if (is_subclass_of($class_name, IGraphQlMapDataResolver::class)){

//             $cl = new $class_name();
//             $cl->setSource($source);
//             return $cl;
//         }
//     }

//     public function test_parse_with_alias_listener()
//     {
 
//         $obj = GraphQlParser::Parse("{localuser: user(id:1){ name } lastname }", self::CreateGraphListener(MockGraphListener::class, [
//             'firstname' => 'C.A.D',
//             'lastname' => 'BONDJE DOUE',
//         ]));
//         $this->assertEquals(
//             json_encode((object)["localuser" => ["name" => "user1"], "lastname" => 'BONDJE DOUE']),
//             json_encode($obj)
//         );
//     }

//     public function test_parse_with_array()
//     { 
//         //without entry field name - consider as global entries 
//         $obj = GraphQlParser::ParseWithOption(
//             [
//                 'noThrowOnMissingProperty'=>true
//             ],
//             "{ user{ name, firstname, lastname } } ",
//         self::CreateGraphListener(MockGraphListener::class, [
            
//             [
//                 'firstname' => 'C.A.D',
//                 'lastname' => 'BONDJE DOUE',
//             ],
//             [
//                 'firstname' => 'TCHATCHO',
//                 'lastname' => 'ROMEO',
//             ],
//             [
//                 'firstname' => 'ISA',
//                 'lastname' => 'HEIJERS',
//             ],
//             [
//                 'firstname' => 'ST',
//                 'lastname' => 'SAYA',
//             ]
//         ]));
//         // + | ass entry object consider as path reading model 
//         $this->assertEquals(
//             json_encode((object)[
//                 "user" => [
//                     [
//                         'name'=>null,
//                         'firstname' => 'C.A.D',
//                         'lastname' => 'BONDJE DOUE',
//                     ],
//                     [
//                         'name'=>null,
//                         'firstname' => 'TCHATCHO',
//                         'lastname' => 'ROMEO',
//                     ],
//                     [
//                         'name'=>null,
//                         'firstname' => 'ISA',
//                         'lastname' => 'HEIJERS',
//                     ],
//                     [
//                         'name'=>null,
//                         'firstname' => 'ST',
//                         'lastname' => 'SAYA',
//                     ],
//                 ]
//             ]),
//             json_encode($obj)
//         );
//     }
//     public function test_parse_with_a_rray_2()
//     { 
//         $obj = GraphQlParser::Parse("# @={noThrowOnMissingProperty} \n{ name, firstname, lastname } ", 
//         self::CreateGraphListener(MockGraphListener::class,[
//             [
//                 'firstname' => 'C.A.D',
//                 'lastname' => 'BONDJE DOUE',
//             ],
//             [
//                 'firstname' => 'TCHATCHO',
//                 'lastname' => 'ROMEO',
//             ],
//             [
//                 'firstname' => 'ISA',
//                 'lastname' => 'HEIJERS',
//             ],
//         ]));
//         $this->assertEquals(
//             json_encode([
                
//                     [
//                         'name'=>null,
//                         'firstname' => 'C.A.D',
//                         'lastname' => 'BONDJE DOUE',
//                     ],
//                     [
//                         'name'=>null,
//                         'firstname' => 'TCHATCHO',
//                         'lastname' => 'ROMEO',
//                     ],
//                     [
//                         'name'=>null,
//                         'firstname' => 'ISA',
//                         'lastname' => 'HEIJERS',
//                     ],
                
//             ]),
//             json_encode($obj)
//         );
//     }

//     public function test_parsing_expression(){
//         $obj = GraphQlParser::Parse("{ n, m }");
//         $this->assertEquals(
//             (object)['m'=>null, 'n'=>null],
//             $obj
//         );
//     }
//     public function test_parsing_without_name(){
//         // query without name 
//         $obj = GraphQlParser::Parse("query{ n, m }");
//         $this->assertEquals(
//             (object)['m'=>null, 'n'=>null],
//             $obj
//         );
//     }
//     public function test_parsing_with_name(){
//         // query with name 
//         $obj = GraphQlParser::Parse("query Ulist{ n, m }");
//         $this->assertEquals(
//             (object)['m'=>null, 'n'=>null],
//             $obj
//         ); 
//     }

//     public function test_parsing_with_naame_and_parameter(){
//         // query with name 
//         $obj = GraphQlParser::Parse("query Ulist(\$id: Int = 30, \$y: Int =5){ n, m }");
//         $this->assertEquals(
//             (object)['m'=>null, 'n'=>null],
//             $obj
//         ); 
//     }

//     public function test_parsing_comment(){
//         // query with name 
//         $obj = GraphQlParser::Parse("#this is a comment\n{ n, m }");
//         $this->assertEquals(
//             (object)['m'=>null, 'n'=>null],
//             $obj
//         ); 
//     }

//     public function test_parsing_read_description(){
//         // query with name 
//         $obj = GraphQlParser::Parse('""" this is a description """'."\nquery { n, m }", null, $parser);        
//         $this->assertEquals(
//             (object)['n'=>null, 'm'=>null],
//             $obj
//         ); 
//         $def = $parser->getDeclaredInputs()['query'];
//         $this->assertEquals('[{"type":"query","definition":{"n":{"name":"n"},"m":{"name":"m"}},"description":"this is a description"}]', 
//             JSon::Encode($def, Activator::CreateNewInstance(JSonEncodeOption::class,['ignore_empty'=>true]))
//         );
//     }

//     // public function test_parsing_arg_string_value(){
//     //     // query with name 
//     //     $obj = GraphQlParser::Parse("\nquery Doto(\$email:\"cadMail\"){ n, m }", $parser);        
//     //     $this->assertEquals(
//     //         (object)['n'=>null, 'm'=>null],
//     //         $obj
//     //     ); 
//     //     $def = $parser->getDeclaredInputs()['query'];
//     //     $this->assertEquals('{"type":"query","definition":{},"description":"this is a description"}', 
//     //         JSon::Encode($def, Activator::CreateNewInstance(JSonEncodeOption::class,['ignore_empty'=>true]))
//     //     );
//     // }


//     public function test_parsing_enum(){
//         // query with name 
//         $obj = GraphQlParser::Parse('""" enumerate cars """ enum CAR{MERCEDES, TOYOTA}', null, $parser);        
       
//         $def = $parser->getDeclaredInputs()['enum'];
//         $this->assertEquals('{"type":"enum","name":"CAR","definition":{"MERCEDES":{"name":"MERCEDES"},"TOYOTA":{"name":"TOYOTA"}},"description":"enumerate cars"}', 
//             JSon::Encode($def[0], Activator::CreateNewInstance(JSonEncodeOption::class,['ignore_empty'=>true]))
//         );
//     }
//     public function test_parsing__enum_with_description(){
//         // query with name 
//         $obj = GraphQlParser::Parse('enum Lang{"Francais" FR, "English" EN}', null, $parser);        
       
//         $def = $parser->getDeclaredInputs()['enum'];
//         $this->assertEquals('[{"type":"enum","name":"Lang","definition":{"FR":{"name":"FR","description":"Francais"},"EN":{"name":"EN","description":"English"}}}]', 
//             JSon::Encode($def, Activator::CreateNewInstance(JSonEncodeOption::class,['ignore_empty'=>true]))
//         );
//     }

//     public function test_parsing_variables(){
//         // query with name 
//         $obj = GraphQlParser::Parse([
//             'query'=>"{userList: user(\$uid:Int=1){ email }}",
//             'variables'=>[
//                 'uid'=>4
//             ]
//         ],new MockGraphListener, $parser);        
       
        
//         $this->assertEquals((object)[
//             'userList'=>[
//                 'email'=>'cbondje@igkdev.com'
//             ]
//         ],
//             $obj
//         );
//     }
//     public function test_parsing_variables_list(){
//         // query with name 
//         $obj = GraphQlParser::Parse([
//             'query'=>"{userList: users(\$uid:Int=1){ email }}",
//             'variables'=>[
//                 'uid'=>4
//             ]
//         ], new MockGraphListener, $parser);        
       
        
//         $this->assertEquals((object)[
//             'userList'=>[
//                 ['email'=>'cbondje@igkdev.com'],
//                 ['email'=>'cbondje@igkdev.be'],
//             ]
//         ],
//             $obj
//         );
//     }
//     // public function test_parsing_variables_with_inject(){
//     //     $ad = SysDbController::ctrl()->getDataAdapter();
//     //     if (!$ad->connect()){
//     //         $this->markTestSkipped('no dataapteer');
//     //         return;
//     //     }
//     //     $ad->close();
//     //     // query with name 
//     //     $obj = GraphQlParser::Parse([
//     //         'query'=>"{userList: usersInject(\$uid:Int=1){ email }}",
//     //         'variables'=>[
//     //             'uid'=>4
//     //         ]
//     //     ], new MockGraphListener, $parser);        
       
        
//     //     $this->assertEquals((object)[
//     //         'userList'=>[
//     //             ['email'=>'patrick.safack@local.tonerafrika.cm'], 
//     //         ]
//     //     ],
//     //         $obj
//     //     );
//     // }

//     // public function test_parsing_variables_with_inject_array(){
//     //     $ad = SysDbController::ctrl()->getDataAdapter();
//     //     if (!$ad->connect()){
//     //         $this->markTestSkipped('no dataapteer');
//     //         return;
//     //     }
//     //     $ad->close();
//     //     // query with name 
//     //     $obj = GraphQlParser::Parse([
//     //         'query'=>"{userList: usersInjectArray(\$uid:Int=1){ email }}",
//     //         'variables'=>[
//     //             'uid'=>4
//     //         ]
//     //     ], new MockGraphListener, $parser);        
       
        
//     //     $this->assertEquals((object)[
//     //         'userList'=>[
//     //             ['email'=>'dummy@test.com'], 
//     //             ['email'=>'vlam@test.com'], 
//     //         ]
//     //     ],
//     //         $obj
//     //     );
//     // }


   
//     public function test_query_default(){
//         // query with name 
//         $obj = GraphQlParser::Parse([
//             'query'=>"{ email, locale}",
//             'variables'=>[
//                 'uid'=>4
//             ]
//         ], new MockGraphListener, $parser);        
       
        
//         $this->assertEquals((object)[             
//             'locale'=>"en" ,
//             'email'=>"t4@local.test" ,            
//         ],
//             $obj
//         );
//     }

//     /**
//      * test spread info
//      * @return void 
//      * @throws IGKException 
//      * @throws GraphQlSyntaxException 
//      * @throws InvalidArgumentException 
//      * @throws ExpectationFailedException 
//      */
//     public function test_spread_read(){ 
//             // query with name 
//             $obj = GraphQlParser::Parse([
//                 'query'=>"{ email, ...locale } fragment locale on User { lang, press }",
//                 'variables'=>[
//                     'uid'=>4
//                 ],
//                 'noThrowOnMissingProperty'=>true
//             ], new MockGraphListener, $parser);        
           
            
//             $this->assertEquals((object)[                 
//                 'email'=>"t4@local.test" ,            
//                 'lang'=>"en" ,
//                 'press'=>"pressing" ,
//             ],
//                 $obj
//             );
//     }

//     /**
//      * test read fragment
//      * @return void 
//      */
//     public function test_read_fragment(){
//         $obj = GraphQlParser::Parse([
//             'query'=>"fragment localFragment on User{name { firstname} } fragment xd on User{firstname} type User{ name, firstname, lastname}",
//             'variables'=>[
//                 'uid'=>4
//             ]
//         ],new MockGraphListener, $parser);

//         $v_fragments = $parser->getFragments();


//         $this->assertEquals('localFragment', $v_fragments[0]->name);
//         $this->assertEquals('User', $v_fragments[0]->on);       
//     }

//     public function test_mocking_inline_no_name(){
//         // | check that chain arg ignore function list and return only access fields
//         $query1 = <<<EOF
//         {
//             " get all production "
//             products(i: 45) {
//                 " id definition "
//                 id
//                 " name definition "
//                 name
//             }
//         }
// EOF;
//         $d1 = GraphQlParser::Parse($query1, new MockingInlineListener());
//         $r = $this->getMockingresult();
//         $this->assertEquals( json_encode($r), json_encode($d1));
//     }

//     /**
//      * ignore the single name query 
//      * @return void 
//      * @throws IGKException 
//      * @throws GraphQlSyntaxException 
//      * @throws ArgumentTypeNotValidException 
//      * @throws ReflectionException 
//      * @throws InvalidArgumentException 
//      * @throws ExpectationFailedException 
//      */
//     public function test_mocking_withToQueryTypeName(){
//         $query1 = <<<EOF
// query toQuery{
//     products(i: 45) {
//         id
//         name
//     }
// }
// EOF;
//         $d1 = GraphQlParser::Parse($query1, new MockingInlineListener());
//         $r = $this->getMockingresult();
//         $this->assertEquals( json_encode($r), json_encode($d1));
//     }
//     public function test_mocking_withToQuery_TypeName2(){
//         $query1 = <<<EOF
// toQuery{
//             products(i: 45) {
//                 id
//                 name
//             }
// }
// EOF;
//         $d1 = GraphQlParser::Parse($query1, new MockingInlineListener());
//         $r = $this->getMockingresult();
//         $this->assertEquals( json_encode($r), json_encode($d1));
//     }
//     protected function getMockingresult(){
//         return json_decode(
// <<<'JSON'
// {
//     "products": [
//         {
//             "id": 1,
//             "name": "cocacola"
//         },
//         {
//             "id": 2,
//             "name": "fanta"
//         }
//     ]
// }
// JSON
// );

//     }

//     public function test_mocking_read_function_array(){
//         $query1 = <<<EOF
// {
//             products(id: 3, limit: [2,45], orderBy:["name","id"]) {
//                 id
//                 name
//             }
// }
// EOF;
//         $d1 = GraphQlParser::Parse($query1, new MockingInlineListener());
//         $r = $this->getMockingresult();
//         $this->assertEquals( json_encode($r), json_encode($d1));
//     }
}

