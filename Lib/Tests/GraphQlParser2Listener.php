<?php
namespace igk\io\GraphQl\Tests;

use igk\io\GraphQl\GraphQlQueryOptions;
use igk\io\GraphQl\IGraphQlInspector;

class GraphQlParser2Listener implements IGraphQlInspector{

    public function query() { 
        return json_decode(file_get_contents(__DIR__."/Data/parser_data.json"));
    }
    public function users(){
        return [
            ['name'=>'C.A.D','login'=>'cbondje@igkdev.com'],
            ['name'=>'CHARLES','login'=>'bondje.doue@igkdev.com']
        ];
    }
    public function userDetails(int $index){
        return igk_getv($this->users(), $index);
    }
    public function selectUser(int $index, GraphQlQueryOptions $option){
        return igk_getv($this->users(), $index);
    }
}