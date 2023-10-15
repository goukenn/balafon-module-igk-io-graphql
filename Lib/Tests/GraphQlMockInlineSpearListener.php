<?php
namespace igk\io\GraphQl\Tests;

use igk\io\GraphQl\IGraphQlInspector;

class GraphQlMockInlineSpearListener implements IGraphQlInspector{
    var $source_type;
    private $m_source_data;
    public function __construct(?string $type)
    {
        $this->source_type = $type;
    }
    public function getSourceTypeName(): ?string{
        return $this->source_type;
    }
    public function setSource($data){
        $this->m_source_data = $data;

    }
    public function query(){
        return $this->m_source_data;
    }
}