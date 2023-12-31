<?php
// @author: C.A.D. BONDJE DOUE
// @file: ModelGraphListener.php
// @date: 20230912 06:57:27
namespace igk\io\GraphQl\Database;

use IGK\Controllers\BaseController;

///<summary></summary>
/**
* reprenset system model graph listener 
* @package igk\io\GraphQl\Database
*/
abstract class ModelGraphListener{
    private $m_controller;
    public function __function (BaseController $controller){
        $this->m_controller = $controller;
    }
    public function getController(){
        return $this->m_controller;
    }
    // define method that will hangle graph query or mutation 
    public abstract function query();
}