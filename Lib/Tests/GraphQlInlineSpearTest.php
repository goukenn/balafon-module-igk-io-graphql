<?php
// @author: C.A.D. BONDJE DOUE
// @file: GraphQlInlineSpearTest.php
// @date: 20231013 08:15:18
namespace igk\io\GraphQl\Tests;

use igk\io\GraphQl\GraphQlParser;

require_once __DIR__ . '/GraphQlTestBase.php';
///<summary></summary>
/**
 * 
 * @package igk\io\GraphQl\Tests
 */
class GraphQlInlineSpearTest extends GraphQlTestBase
{
    public function test_inline_spear()
    {
        $o = GraphQlParser::Parse(self::_lib(__FUNCTION__));
        $o =  json_encode($o);
        // condition missing no detecting spear
        $this->assertEquals(
            json_encode((object)['name' => null]),
            $o
        );
    }
    public function test_inline_ddspear_with()
    {
        $src = self::_lib('test_inline_spear');
        $o = GraphQlParser::Parse(
            $src,
            new GraphQlMockInlineSpearListener('User')
        );
        $o =  json_encode($o);
        $this->assertEquals(
            json_encode((object)['Query'=>null]),
            $o
        );

        $o = GraphQlParser::Parse(
            $src,
            new GraphQlMockInlineSpearListener('Person')
        );
        $o =  json_encode($o);
        $this->assertEquals(
            json_encode((object)['Query'=>null]),
            $o
        );
    }

    public function test_inline_data_spear_with()
    {
        $src = self::_lib('test_inline_spear');
        $p = new GraphQlMockInlineSpearListener('User');
        $p->setSource([
            ['name' => 'Bondje'],
            ['name' => 'One Bondje'],
        ]);
        $o = GraphQlParser::Parse(
            $src,
            $p
        );
        $o =  json_encode($o);
        $this->assertEquals(
            json_encode((object)["Query"=>[
                ['name' => 'Bondje', 'firstName' => null, 'lastName' => null, 'gender' => null],
                ['name' => 'One Bondje', 'firstName' => null, 'lastName' => null, 'gender' => null]
            ]]),
            $o
        );
    }
    public function test_inline_spear_profile()
    {
        $src = self::_lib(__FUNCTION__);
        $p = new GraphQlMockInlineSpearListener('User');
        $p->setSource([
            ['name' => 'Bondje', 'profile'=>1],
            ['name' => 'One Bondje', 'profile'=>2],
        ]);
        $o = GraphQlParser::Parse(
            $src,
            $p
        );
        $o =  json_encode($o);
        $this->assertEquals(
            json_encode((object)['Query'=>[
                ['name' => 'Bondje', 'firstName' => null, 'lastName' => null, 'gender' => null, 'profile'=>1],
                ['name' => 'One Bondje', 'firstName' => null, 'lastName' => null, 'gender' => null,'profile'=>2]
            ]]),
            $o
        );
    }


    public function test_profile_sublist()
    {
        $src = self::_lib(__FUNCTION__);
        $p = new GraphQlMockInlineSpearListener('User');
        $p->setSource([
            ['name' => 'ONE', 'profiles'=>['picture'=>'img1.jpg','size'=>'2X', 'loc'=>'55J']],
            ['name' => 'TWO', 'profiles'=>['picture'=>'img2.jpg','size'=>'5X']],
        ]);
        $o = GraphQlParser::Parse(
            $src,
            $p
        );
        $o =  json_encode($o);
        $this->assertEquals(
            json_encode((object)['Query'=>[
                ['name' => 'ONE', 'profiles' => ['picture'=>'img1.jpg', 'size' => '2X', 'loc'=>'55J']],
                ['name' => 'TWO', 'profiles' => ['picture'=>'img2.jpg', 'size' => '5X', 'loc'=>-1]]
            ]]),
            $o
        );
    }
}
