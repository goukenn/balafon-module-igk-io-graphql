<?php
// @author: C.A.D. BONDJE DOUE
// @file: GraphQlDeclaredType.php
// @date: 20230926 09:16:41
namespace igk\io\GraphQl;

use IGK\Helper\Activator;

///<summary></summary>
/**
* 
* @package igk\io\GraphQl
*/
class GraphQlDeclaredType{
    /**
     * type name
     * @var mixed
     */
    var $name;
    /**
     * type description
     * @var mixed
     */
    var $description;

    var $type = 'type';

    var $parent = null;


    public function readDefinition($reader): bool{
        $definitions = [];
        $brank = 0;
        $v_desc = null;
        while($reader->read()){
            list($id, $v, $e) = $reader->tokenInfo();
          
            if (($brank==0) && $id== GraphQlReaderConstants::T_READ_END){
                break;
            }
            switch($id){
                case GraphQlReaderConstants::T_READ_START:
                    $brank++;
                    break;
                case GraphQlReaderConstants::T_READ_END:
                    $brank--; 
                    break;
                case GraphQlReaderConstants::T_READ_NAME: 
                        $definitions[$v] = Activator::CreateNewInstance( GraphQlDeclaredTypePropertyInfo::class,
                         ['name'=>$v, 'description'=>$v_desc]);
                        $v_desc = null; 
                    break;
                case GraphQlReaderConstants::T_READ_DESCRIPTION:
                     $v_desc = $v;
                    break;
            }
        } 
        return true;
    }

}