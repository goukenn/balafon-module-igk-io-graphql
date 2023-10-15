<?php


namespace igk\io\GraphQl\Tests;

use igk\io\GraphQl\GraphQlQueryOptions;
use igk\io\GraphQl\IGraphQlInspector;

class MockingInlineListener implements IGraphQlInspector{

    private $m_sourceType;

    public function getSourceTypeName(): ?string
    {
        return $this->m_sourceType;
    }
    public function getMapData(string $typeName) { }
    /**
     * query base listener 
     * @return null 
     */
    public function query(){
        return null;
    }
    public function products(GraphQlQueryOptions $options, $product = null){ 
        return [
            (object)['id'=>1, 'name'=>'cocacola'] ,
            (object)['id'=>2, 'name'=>'fanta'] ,
        ];
    }
}