<?php
// @author: C.A.D. BONDJE DOUE
// @file: GraphQlProjectDbIntrospection.php
// @date: 20231016 10:27:09
namespace igk\io\GraphQl\Schemas;

use IGK\Controllers\BaseController;
use igk\io\GraphQl\System\Database\Helpers\GraphQlDbHelper;

///<summary></summary>
/**
* 
* @package igk\io\GraphQl\Schemas
*/
class GraphQlProjectDbIntrospection{
    private $m_ctrl;
    private $m_source_class;
    public function __construct(BaseController $controller, string $source_class)
    {
        $this->m_ctrl = $controller;
        $this->m_source_class = $source_class;
    }
    /**
     * indicator source class used for introspection
     * @return string 
     */
    public function getSourceClass(){
        return $this->m_source_class;
    }
    public function buildSchema($schema, $v_queryobj){
        GraphQlDbHelper::BuildIntrospectSchema($this->m_ctrl, $schema, $v_queryobj);
    }
}