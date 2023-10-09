<?php
// @author: C.A.D. BONDJE DOUE
// @file: GraphQlDeclaredInputFactory.php
// @date: 20230921 17:24:31
namespace igk\io\GraphQl;


///<summary></summary>
/**
* 
* @package igk\io\GraphQl
*/
abstract class GraphQlDeclaredInputFactory extends GraphQlDeclaredInput{
    public static function  Create(string $n){
        $cl = __NAMESPACE__."\\".ucfirst($n)."DeclaredInput";
    
        if (class_exists($cl) && is_subclass_of($cl, GraphQlDeclaredInput::class)){
 
            $p = new $cl();
            $p->type = $n;
            return $p;
        } 
    }
}