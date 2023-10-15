<?php
// @author: C.A.D. BONDJE DOUE
// @file: GraphQlReadCommentOptionsTrait.php
// @date: 20231014 17:40:01
namespace igk\io\GraphQl\Traits;

use IGK\Helper\Activator;

///<summary></summary>
/**
* 
* @package igk\io\GraphQl
*/
trait GraphQlReadCommentOptionsTrait{ 
     /**
     * false
     * @var false
     */
    var $noSkipFirstNamedQueryEntry = false;

    /**
     * not throw on missing property
     * @var false
     */
    var $noThrowOnMissingProperty = false;

    

    public function copyState($i){
        
        foreach ([
            'noSkipFirstNamedQueryEntry',
            'noThrowOnMissingProperty',
        ] as $k){
            $this->{$k} = igk_getv($i, $k);
        }
    }
}