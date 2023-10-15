<?php
// @author: C.A.D. BONDJE DOUE
// @file: GraphQlHooks.php
// @date: 20231009 17:40:02
namespace igk\io\GraphQl;

use IGK\System\IO\Path;
use IGK\System\Traits\HookNameTrait;

///<summary></summary>
/**
* 
* @package igk\io\GraphQl
*/
abstract class GraphQlHooks{
    const HOOK_END_SECTION = 'endSection';
    const HOOK_LOAD_COMPLETE= 'loadComplete';
    /**
     * invoke when root level of a query reach
     */
    const HOOK_END_QUERY = 'hookEndQuerySection'; 
    const HOOK_END_ENTRY = 'endEntry';
    const HOOK_SECTION_FUNC = 'hookSectionFunc';
    use HookNameTrait;
     
}