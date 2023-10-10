<?php
// @author: C.A.D. BONDJE DOUE
// @file: GraphReadSectionInfoTest.php
// @date: 20231010 15:34:47
namespace igk\io\GraphQl;

use IGK\Database\DbColumnInfo;
use IGK\Database\DbColumnInfoPropertyConstants;
use IGK\Models\ModelBase;
use IGK\Tests\Controllers\ModuleBaseTestCase;

///<summary></summary>
/**
* 
* @package igk\io\GraphQl
*/
class GraphReadSectionInfoTest extends ModuleBaseTestCase{
    public function test_read_property(){
        $inf = new GraphQlReadSectionInfo;
        $inf->properties['x'] = new GraphQlPropertyInfo('x');
        $inf->properties['y'] = new GraphQlPropertyInfo('y');

        $r = $inf->getData($data=[
            'x'=>9,
            'y'=>10
        ]);
        $this->assertEquals($data, $r);
    }
    public function test_read_extra_property(){
        $inf = new GraphQlReadSectionInfo;
        $inf->properties['x'] = new GraphQlPropertyInfo('x');
        $inf->properties['y'] = new GraphQlPropertyInfo('y');

        $r = $inf->getData($data=[
            'x'=>9,
            'y'=>10,
            'u'=>333
        ]);
        unset($data['u']);
        $this->assertEquals($data, $r);
    }

    public function test_read_extra_indexed_property(){
        $inf = new GraphQlReadSectionInfo;
        $inf->properties['x'] = new GraphQlPropertyInfo('x');
        $inf->properties['y'] = new GraphQlPropertyInfo('y');

        $r = $inf->getData($data=[
            ['x'=>9,
            'y'=>10],
            ['x'=>9,
            'g'=>10, 'y'=>'o'],
            ['x'=>9,
            'g'=>10, 'y'=>'m'], 
        ]); 
        $this->assertEquals([
            ['x'=>9,'y'=>10],
            ['x'=>9,'y'=>'o'],
            ['x'=>9,'y'=>'m'],
        ], $r);
    }
    public function test_read_extra_indexed_model_property(){
        $inf = new GraphQlReadSectionInfo;
        $inf->properties['name'] = new GraphQlPropertyInfo('name');
        $inf->properties['lastname'] = new GraphQlPropertyInfo('lastname');

        $r = $inf->getData( [
            new DbModelMockModel(['name'=>'C.A.D']),
            new DbModelMockModel(['name'=>'C.A.D', 'lastname'=>'BONDJE']), 
        ]); 
        $this->assertEquals([
            ['name'=>'C.A.D', 'lastname'=>null], 
            ['name'=>'C.A.D', 'lastname'=>'BONDJE'], 
        ], $r);
    }
}


class DbModelMockModel extends ModelBase{
    public function __construct($raw){
        $this->is_mock = true;
        $this->raw = $raw;
    }
    public function getTableColumnInfo()
    {
        return [
            'name'=>new DbColumnInfo(["clName"=>'name']),
            'lastName'=>new DbColumnInfo(["clName"=>'lastName'])
        ];
    }
    public function getTableInfo(){
        return [
            'columnInfo'=>$this->getTableColumnInfo(),
            DbColumnInfoPropertyConstants::DefTableName=>__CLASS__
        ];
    }
    public function getTable()
    {
        return 'dummy';
    }
}