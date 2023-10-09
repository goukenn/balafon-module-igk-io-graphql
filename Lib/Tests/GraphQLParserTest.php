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
use igk\io\GraphQl\GraphQlParser;
use igk\io\GraphQl\GraphQlQueryOptions;
use IGK\System\Exceptions\EnvironmentArrayException;
use IGK\Tests\Controllers\ModuleBaseTestCase;
use IGKException;

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
        $obj = GraphQlParser::Parse("{}");
        $this->assertIsObject($obj);
    }

    public function test_parse_object_2()
    {
        $obj = GraphQlParser::Parse("{name,firstname,lastname}");
        $this->assertEquals(
            json_encode((object)["name" => null, "firstname" => null, "lastname" => null]),
            json_encode($obj)
        );
    }

    public function test_parse_object_3()
    {
        $obj = GraphQlParser::Parse("{name: String = 'charles' }");
        $this->assertEquals(
            json_encode((object)["name" => 'charles']),
            json_encode($obj)
        );
    }
    public function test_parse_object_4()
    {
        $obj = GraphQlParser::Parse(
            <<<'GQL'
{
    name
    firstname
    lastname
}
GQL
        );
        $this->assertEquals(
            json_encode((object)["name" => null, "firstname" => null, "lastname" => null]),
            json_encode($obj)
        );
    }

    public function test_parse_with_data()
    {
        $obj = GraphQlParser::Parse("{name,firstname,lastname}", [
            'firstname' => 'C.A.D',
            'lastname' => 'BONDJE DOUE',
        ]);
        $this->assertEquals(
            json_encode((object)["name" => null, "firstname" => 'C.A.D', "lastname" => 'BONDJE DOUE']),
            json_encode($obj)
        );
    }
    public function test_parse_with_data_2()
    {

        $obj = GraphQlParser::Parse("{name,firstname,lastname,age:Int}", [
            'firstname' => 'C.A.D',
            'lastname' => 'BONDJE DOUE',
        ]);
        $this->assertEquals(
            json_encode((object)["name" => null, "firstname" => 'C.A.D', "lastname" => 'BONDJE DOUE', 'age' => 0]),
            json_encode($obj)
        );
    }

    public function test_parse_listener()
    {

        $obj = GraphQlParser::Parse("{user (id:1){ name } lastname }", [
            'firstname' => 'C.A.D',
            'lastname' => 'BONDJE DOUE',
        ], new MockGraphListener);
        $this->assertEquals(
            json_encode((object)["user" => ["name" => "user1"], "lastname" => 'BONDJE DOUE']),
            json_encode($obj)
        );
    }

    public function test_parse_with_alias_listener()
    {

        $obj = GraphQlParser::Parse("{localuser: user(id:1){ name } lastname }", [
            'firstname' => 'C.A.D',
            'lastname' => 'BONDJE DOUE',
        ], new MockGraphListener);
        $this->assertEquals(
            json_encode((object)["localuser" => ["name" => "user1"], "lastname" => 'BONDJE DOUE']),
            json_encode($obj)
        );
    }

    public function test_parse_with_array()
    { 
        $obj = GraphQlParser::Parse("{ user{ name, firstname, lastname } } ", [
            [
                'firstname' => 'C.A.D',
                'lastname' => 'BONDJE DOUE',
            ],
            [
                'firstname' => 'TCHATCHO',
                'lastname' => 'ROMEO',
            ],
            [
                'firstname' => 'ISA',
                'lastname' => 'HEIJERS',
            ],
        ], new MockGraphListener);
        $this->assertEquals(
            json_encode((object)[
                "user" => [
                    [
                        'name'=>null,
                        'firstname' => 'C.A.D',
                        'lastname' => 'BONDJE DOUE',
                    ],
                    [
                        'name'=>null,
                        'firstname' => 'TCHATCHO',
                        'lastname' => 'ROMEO',
                    ],
                    [
                        'name'=>null,
                        'firstname' => 'ISA',
                        'lastname' => 'HEIJERS',
                    ],
                ]
            ]),
            json_encode($obj)
        );
    }
    public function test_parse_with_array_2()
    { 
        $obj = GraphQlParser::Parse("{ name, firstname, lastname } ", [
            [
                'firstname' => 'C.A.D',
                'lastname' => 'BONDJE DOUE',
            ],
            [
                'firstname' => 'TCHATCHO',
                'lastname' => 'ROMEO',
            ],
            [
                'firstname' => 'ISA',
                'lastname' => 'HEIJERS',
            ],
        ], new MockGraphListener);
        $this->assertEquals(
            json_encode((object)[
                
                    [
                        'name'=>null,
                        'firstname' => 'C.A.D',
                        'lastname' => 'BONDJE DOUE',
                    ],
                    [
                        'name'=>null,
                        'firstname' => 'TCHATCHO',
                        'lastname' => 'ROMEO',
                    ],
                    [
                        'name'=>null,
                        'firstname' => 'ISA',
                        'lastname' => 'HEIJERS',
                    ],
                
            ]),
            json_encode($obj)
        );
    }

    public function test_parsing_expression(){
        $obj = GraphQlParser::Parse("{ n, m }",[], null);
        $this->assertEquals(
            (object)['m'=>null, 'n'=>null],
            $obj
        );
    }
    public function test_parsing_without_name(){
        // query without name 
        $obj = GraphQlParser::Parse("query{ n, m }",[], null);
        $this->assertEquals(
            (object)['m'=>null, 'n'=>null],
            $obj
        );
    }
    public function test_parsing_with_name(){
        // query with name 
        $obj = GraphQlParser::Parse("query Ulist{ n, m }",[], null);
        $this->assertEquals(
            (object)['Ulist'=>['m'=>null, 'n'=>null]],
            $obj
        ); 
    }

    public function test_parsing_with_naame_and_parameter(){
        // query with name 
        $obj = GraphQlParser::Parse("query Ulist(\$id: Int = 30, \$y: Int =5){ n, m }",[], null);
        $this->assertEquals(
            (object)['Ulist'=>['m'=>null, 'n'=>null]],
            $obj
        ); 
    }

    public function test_parsing_comment(){
        // query with name 
        $obj = GraphQlParser::Parse("#this is a comment\n{ n, m }",[], null);
        $this->assertEquals(
            (object)['m'=>null, 'n'=>null],
            $obj
        ); 
    }

    public function test_parsing_read_description(){
        // query with name 
        $obj = GraphQlParser::Parse('""" this is a description """'."\nquery { n, m }",[], null, $parser);        
        $this->assertEquals(
            (object)['n'=>null, 'm'=>null],
            $obj
        ); 
        $def = $parser->getDeclaredInputs()['query'];
        $this->assertEquals('[{"type":"query","definition":{"n":{"name":"n"},"m":{"name":"m"}},"description":"this is a description"}]', 
            JSon::Encode($def, Activator::CreateNewInstance(JSonEncodeOption::class,['ignore_empty'=>true]))
        );
    }

    // public function test_parsing_arg_string_value(){
    //     // query with name 
    //     $obj = GraphQlParser::Parse("\nquery Doto(\$email:\"cadMail\"){ n, m }",[], null, $parser);        
    //     $this->assertEquals(
    //         (object)['n'=>null, 'm'=>null],
    //         $obj
    //     ); 
    //     $def = $parser->getDeclaredInputs()['query'];
    //     $this->assertEquals('{"type":"query","definition":{},"description":"this is a description"}', 
    //         JSon::Encode($def, Activator::CreateNewInstance(JSonEncodeOption::class,['ignore_empty'=>true]))
    //     );
    // }


    public function test_parsing_enum(){
        // query with name 
        $obj = GraphQlParser::Parse('enum CAR{MERCEDES, TOYOTA}',[], null, $parser);        
       
        $def = $parser->getDeclaredInputs()['enum'];
        $this->assertEquals('{"type":"enum","name":"CAR","definition":{"MERCEDES":{"name":"MERCEDES"},"TOYOTA":{"name":"TOYOTA"}}}', 
            JSon::Encode($def[0], Activator::CreateNewInstance(JSonEncodeOption::class,['ignore_empty'=>true]))
        );
    }
    public function test_parsing_enum_with_description(){
        // query with name 
        $obj = GraphQlParser::Parse('enum Lang{"Francais" FR, "English" EN}',[], null, $parser);        
       
        $def = $parser->getDeclaredInputs()['enum'];
        $this->assertEquals('[{"type":"enum","name":"Lang","definition":{"FR":{"name":"FR","description":"Francais"},"EN":{"name":"EN","description":"English"}}}]', 
            JSon::Encode($def, Activator::CreateNewInstance(JSonEncodeOption::class,['ignore_empty'=>true]))
        );
    }

    public function test_parsing_variables(){
        // query with name 
        $obj = GraphQlParser::Parse([
            'query'=>"{userList: user(\$uid:Int=1){ email }}",
            'variables'=>[
                'uid'=>4
            ]
        ],[], new MockGraphListener, $parser);        
       
        
        $this->assertEquals((object)[
            'userList'=>[
                'email'=>'cbondje@igkdev.com'
            ]
        ],
            $obj
        );
    }
    public function test_parsing_variables_list(){
        // query with name 
        $obj = GraphQlParser::Parse([
            'query'=>"{userList: users(\$uid:Int=1){ email }}",
            'variables'=>[
                'uid'=>4
            ]
        ],[], new MockGraphListener, $parser);        
       
        
        $this->assertEquals((object)[
            'userList'=>[
                ['email'=>'cbondje@igkdev.com'],
                ['email'=>'cbondje@igkdev.be'],
            ]
        ],
            $obj
        );
    }
    public function test_parsing_variables_with_inject(){
        $ad = SysDbController::ctrl()->getDataAdapter();
        if (!$ad->connect()){
            $this->markTestSkipped('no dataapteer');
            return;
        }
        $ad->close();
        // query with name 
        $obj = GraphQlParser::Parse([
            'query'=>"{userList: usersInject(\$uid:Int=1){ email }}",
            'variables'=>[
                'uid'=>4
            ]
        ],[], new MockGraphListener, $parser);        
       
        
        $this->assertEquals((object)[
            'userList'=>[
                ['email'=>'patrick.safack@local.tonerafrika.cm'], 
            ]
        ],
            $obj
        );
    }

    public function test_parsing_variables_with_inject_array(){
        $ad = SysDbController::ctrl()->getDataAdapter();
        if (!$ad->connect()){
            $this->markTestSkipped('no dataapteer');
            return;
        }
        $ad->close();
        // query with name 
        $obj = GraphQlParser::Parse([
            'query'=>"{userList: usersInjectArray(\$uid:Int=1){ email }}",
            'variables'=>[
                'uid'=>4
            ]
        ],[], new MockGraphListener, $parser);        
       
        
        $this->assertEquals((object)[
            'userList'=>[
                ['email'=>'dummy@test.com'], 
                ['email'=>'vlam@test.com'], 
            ]
        ],
            $obj
        );
    }


    public function test_mutation(){
        // query with name 
        $obj = GraphQlParser::Parse([
            'query'=>"mutation{updateUser(uid:'charles'){ name }}",
            'variables'=>[
                'uid'=>4
            ]
        ],[], new MockGraphListener, $parser);        
       
        
        $this->assertEquals((object)[
            'updateUser'=>[ 
                'name'=>"charles_update"
            ]
        ],
            $obj
        );
    }
    public function test_mutation_array(){
        // query with name 
        $obj = GraphQlParser::Parse([
            'query'=>"mutation{updateUserArray(uid:'charles'){ name }}",
            'variables'=>[
                'uid'=>4
            ]
        ],[], new MockGraphListener, $parser);        
       
        
        $this->assertEquals((object)[
            'updateUserArray'=>[ 
                ['name'=>"charles_update"],
                ['name'=>"charles_2update"]
            ]
        ],
            $obj
        );
    }

    public function test_mutation_key_array(){
        // query with name 
        $obj = GraphQlParser::Parse([
            'query'=>"mutation{updateUserArray(uid:'charles'){ kUser { name } }}",
            'variables'=>[
                'uid'=>4
            ]
        ],[], new MockGraphListener, $parser);        
       
        
        $this->assertEquals((object)[
            'updateUserArray'=>[ 
                'kUser'=>[
                    ['name'=>"charles_update"],
                    ['name'=>"charles_2update"]
                ]
            ]
        ],
            $obj
        );
    }
    public function test_mutation_change_lang(){
        // query with name 
        $ad = SysDbController::ctrl()->getDataAdapter();

        if (!$ad->connect()){
            $this->markTestSkipped('data adapter can not connect');
            return;
        }
        $ad->close();
        $obj = GraphQlParser::Parse([
            'query'=>"mutation{changeLang(id: \$uid, locale: 'en'){ locale, id } }",
            'variables'=>[
                'uid'=>4
            ]
        ],[], new MockGraphListener, $parser);        
       
        
        $this->assertEquals((object)[
            'changeLang'=>[ 
                 'locale'=>"en" ,
                 'id'=>"4" ,
            ]
        ],
            $obj
        );
    }

    public function test_query_default(){
        // query with name 
        $obj = GraphQlParser::Parse([
            'query'=>"{ email, locale}",
            'variables'=>[
                'uid'=>4
            ]
        ],[], new MockGraphListener, $parser);        
       
        
        $this->assertEquals((object)[
             
            'locale'=>"en" ,
            'email'=>"t4@local.test" ,            
        ],
            $obj
        );
    }

    public function test_spread_read(){ 
            // query with name 
            $obj = GraphQlParser::Parse([
                'query'=>"{ email, ...locale } fragment locale on User { lang, press }",
                'variables'=>[
                    'uid'=>4
                ]
            ],null, new MockGraphListener, $parser);        
           
            
            $this->assertEquals((object)[                 
                'email'=>"t4@local.test" ,            
                'lang'=>"en" ,
                'press'=>"pressing" ,
            ],
                $obj
            );
    }

    /**
     * test read fragment
     * @return void 
     */
    public function test_read_fragment(){
        $obj = GraphQlParser::Parse([
            'query'=>"fragment localFragment on User{name { firstname} } fragment xd on User{firstname} type User{ name, firstname, lastname}",
            'variables'=>[
                'uid'=>4
            ]
        ],[], new MockGraphListener, $parser);

        $v_fragments = $parser->getFragments();


        $this->assertEquals('localFragment', $v_fragments[0]->name);
        $this->assertEquals('User', $v_fragments[0]->on);       
    }

    public function test_mocking_inline_no_name(){
        // | check that chain arg ignore function list and return only access fields
        $query1 = <<<EOF
        {
            products(i: 45) {
                id
                name
            }
        }
EOF;
        $d1 = GraphQlParser::Parse($query1, [], new MockingInlineListener());
        $r = $this->getMockingresult();
        $this->assertEquals( json_encode($r), json_encode($d1));
    }

    public function test_mocking_withToQueryTypeName(){
        $query1 = <<<EOF
query toQuery{
            products(i: 45) {
                id
                name
            }
}
EOF;
        $d1 = GraphQlParser::Parse($query1, [], new MockingInlineListener());
        $r = $this->getMockingresult();
        $this->assertEquals( json_encode($r), json_encode($d1));
    }
    public function test_mocking_withToQueryTypeName2(){
        $query1 = <<<EOF
toQuery{
            products(i: 45) {
                id
                name
            }
}
EOF;
        $d1 = GraphQlParser::Parse($query1, [], new MockingInlineListener());
        $r = $this->getMockingresult();
        $this->assertEquals( json_encode($r), json_encode($d1));
    }
    protected function getMockingresult(){
        return json_decode(<<<EOF
        {
            "products": [
                {
                    "id": 1,
                    "name": "cocacola"
                },
                {
                    "id": 2,
                    "name": "fanta"
                }
            ]
        }
EOF);

    }

    public function test_mocking_read_function_array(){
        $query1 = <<<EOF
{
            products(id: 3, limit: [2,45], orderBy:["name","id"]) {
                id
                name
            }
}
EOF;
        $d1 = GraphQlParser::Parse($query1, [], new MockingInlineListener());
        $r = $this->getMockingresult();
        $this->assertEquals( json_encode($r), json_encode($d1));
    }
}



class MockingInlineListener{
    /**
     * query base listener 
     * @return null 
     */
    public function query(){
        return null;
    }
    public function products(GraphQlQueryOptions $options, $product = null){ 
        // igk_wln_e($options->limit, json_encode($options));

        return [
            (object)['id'=>1, 'name'=>'cocacola'] ,
            (object)['id'=>2, 'name'=>'fanta'] ,
        ];
    }
}