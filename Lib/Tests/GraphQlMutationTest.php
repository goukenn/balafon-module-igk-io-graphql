<?php
// @author: C.A.D. BONDJE DOUE
// @file: GraphQlMutationTest.php
// @date: 20231013 07:11:32
namespace igk\io\GraphQl\Tests;

use IGK\Controllers\SysDbController;
use igk\io\GraphQl\GraphQlParser;
use IGK\Tests\Controllers\ModuleBaseTestCase;

///<summary></summary>
/**
* 
* @package igk\io\GraphQl\Tests
*/
class GraphQlMutationTest extends ModuleBaseTestCase{

    public function test_expection_execute_in_mutation_context(){
        $obj = GraphQlParser::Parse([
            'query'=>"mutation{checkIsInMutation(){ type }}",
            'variables'=>[
                'uid'=>4
            ]
        ], new MockGraphListener, $parser);  
        $this->assertEquals(json_encode([
            'checkIsInMutation'=>[ 
                'type'=>true
            ]
        ]),
            json_encode($obj)
        );
    }
    public function test_mutation(){
        // query with name 
        $obj = GraphQlParser::Parse([
            'query'=>"mutation{updateUser(uid:'charles'){ name }}",
            'variables'=>[
                'uid'=>4
            ]
        ], new MockGraphListener, $parser);  
        $this->assertEquals(json_encode([
            'updateUser'=>[ 
                'name'=>"charles_update"
            ]
        ]),
            json_encode($obj)
        );
    }

    public function test_mutation_with_global_variables(){
        // query with name 
        $obj = GraphQlParser::Parse([
            'query'=>"mutation predicate(\$uid: String){updateUser(uid:\$uid){ name }}",
            'variables'=>[
                'uid'=>'predicate'
            ]
        ], new MockGraphListener, $parser);  
        $this->assertEquals(json_encode([
            'updateUser'=>[ 
                'name'=>"predicate_update"
            ]
        ]),
            json_encode($obj)
        );
    }
    public function test_mutation_with_global_variables_2(){

        $obj = GraphQlParser::Parse([
            'query'=>"mutation predicate(\$uid: String=bmw){updateUser(uid:\$uid){ name }}",
            'variables'=>[
                 //'uid'=>'predicate'
            ]
        ], new MockGraphListener, $parser);  
        $this->assertEquals(json_encode([
            'updateUser'=>[ 
                'name'=>"bmw_update"
            ]
        ]),
            json_encode($obj)
        );
    }
    // public function test_mutation_array(){
    //     // query with name 
    //     $obj = GraphQlParser::Parse([
    //         'query'=>"mutation{updateUserArray(uid:'charles'){ name }}",
    //         'variables'=>[
    //             'uid'=>4
    //         ]
    //     ], new MockGraphListener, $parser);        
       
        
    //     $this->assertEquals((object)[
    //         'updateUserArray'=>[ 
    //             ['name'=>"charles_update"],
    //             ['name'=>"charles_2update"]
    //         ]
    //     ],
    //         $obj
    //     );
    // }

    // public function test_mutation_key_array(){
    //     // query with name 
    //     igk_debug(1);
    //     $obj = GraphQlParser::Parse([
    //         'query'=>"mutation{updateUserArray(uid:'charles'){ kUser { name } }}",
    //         'variables'=>[
    //             'uid'=>4
    //             ]
    //         ], new MockGraphListener, $parser);        
            
    //         igk_debug(0);
        
    //     $this->assertEquals((object)[
    //         'updateUserArray'=>[ 
    //             'kUser'=>[
    //                 ['name'=>"charles_update"],
    //                 ['name'=>"charles_2update"]
    //             ]
    //         ]
    //     ],
    //         $obj
    //     );
    // }
    // public function test_mutation_change_lang(){
    //     // query with name 
    //     $ad = SysDbController::ctrl()->getDataAdapter();

    //     if (!$ad->connect()){
    //         $this->markTestSkipped('data adapter can not connect');
    //         return;
    //     }
    //     $ad->close();
    //     $obj = GraphQlParser::Parse([
    //         'query'=>"mutation{changeLang(id: \$uid, locale: 'en'){ locale, id } }",
    //         'variables'=>[
    //             'uid'=>4
    //         ]
    //     ], new MockGraphListener, $parser);        
       
        
    //     $this->assertEquals((object)[
    //         'changeLang'=>[ 
    //              'locale'=>"en" ,
    //              'id'=>"4" ,
    //         ]
    //     ],
    //         $obj
    //     );
    // }

}