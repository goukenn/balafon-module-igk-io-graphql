<?php
namespace igk\io\GraphQl\Tests;

use igk\io\GraphQl\GraphQlQueryOptions;
use igk\io\GraphQl\IGraphQlInspector;

class GraphQlParser2Listener implements IGraphQlInspector{

    private $m_sourceType;

    public function getSourceTypeName(): ?string
    {
        return $this->m_sourceType;
    }
    public function query() { 
        return json_decode(file_get_contents(__DIR__."/Data/parser_data.json"));
    }
    public function users( GraphQlQueryOptions $option=null){
        $data = $option->data;
        $option->stopIndexing();
        return [
            'name'=> $data->clFirstName,
            'login'=>$data->clLogin 
        ]; 
    }
    public function userDetails(int $index){
        return igk_getv($this->users(), $index);
    }
    public function selectUser(int $index, GraphQlQueryOptions $option){
        return igk_getv($this->users(), $index);
    }
}