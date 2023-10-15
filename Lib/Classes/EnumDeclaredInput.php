<?php
// @author: C.A.D. BONDJE DOUE
// @file: EnumDeclaredInput.php
// @date: 20230921 19:02:42
namespace igk\io\GraphQl;


///<summary></summary>
/**
* 
* @package igk\io\GraphQl
*/
class EnumDeclaredInput extends GraphQlDeclaredInput{
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
                    if ($brank==0){
                        $this->name = $v;
                    }else{

                        $definitions[$v] = ['name'=>$v, 'description'=>$v_desc];
                        $v_desc = null;
                    }
                    break;
                case GraphQlReaderConstants::T_READ_DESCRIPTION:
                    $v_desc = $v;
                    break;
            }
        }
        $this->definition = $definitions;
        return true;
    }
}