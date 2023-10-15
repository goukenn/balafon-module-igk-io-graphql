<?php
// @author: C.A.D. BONDJE DOUE
// @file: MutationDeclaredInput.php
// @date: 20230921 17:29:32
namespace igk\io\GraphQl;

use igk\io\GraphQl\System\IO\GraphQlSectionReader;

///<summary></summary>
/**
* 
* @package igk\io\GraphQl
*/
class MutationDeclaredInput extends GraphQlDeclaredInput{
    var $argument;

    public function readDefinition($reader): bool{

        // read mutation and invoke metho to mutate data 
        $brank = 0; 
        $end = false;
        $mode = 0;
        $section_reader = new GraphQlSectionReader($reader);
        // read function name - read section definition 
        while(!$end && $reader->read()){
            list($id, $v) = $reader->tokenInfo();  
          
            switch ($id){
                case GraphQlReaderConstants::T_READ_END:
                    $brank--;
                    if ($brank == 0){
                        $end = true;
                    }
                    break;
                case GraphQlReaderConstants::T_READ_FUNC_ARGS:
                    if ($brank==0){
                        $this->argument = $v;
                    }
                    break;
                case GraphQlReaderConstants::T_READ_START:
                    $brank++;
                    if ($o = $section_reader->read()){
                        $this->definition = $o;
                        return true;
                    } else {
                        throw new GraphQlSyntaxException("failed reading mutation section");
                    }
                    // $brank - 1 start reading mutation definition 
                    // $brank > 1 expected definition response 
                    break;
                case GraphQlReaderConstants::T_READ_NAME:
                    // read function name 
                    if ($brank==0){
                        $this->name = $v;
                    }
                    break;
            }
        } 
        
        return true;
    }
}