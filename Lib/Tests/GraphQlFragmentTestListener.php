<?php

namespace igk\io\GraphQl\Tests;

use igk\io\GraphQl\IGraphQlInspector;

class GraphQlFragmentTestListener implements IGraphQlInspector
{
    
    public function query(){
        return [
            'fragmentUsers'=>[
                [
                    'login'=>'cbondje@igkdev.com',
                    'street'=>'A',
                    'number'=>10,
                    'box'=>'J'
                ],
                [
                    'login'=>'bondje.doue@igkdev.com',
                    'street'=>'B',
                    'number'=>7,
                    'box'=>'G'
                ]
            ]
        ];
    }
}