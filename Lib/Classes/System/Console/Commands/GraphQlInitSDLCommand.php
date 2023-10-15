<?php
// @author: C.A.D. BONDJE DOUE
// @file: GraphQlInitSDLCommand.php
// @date: 20231015 23:25:51
namespace igk\io\GraphQl\System\Console\Commands;


use igk\io\GraphQl\System\Database\Helpers\GraphQlDbHelper;
use IGK\System\Console\AppExecCommand;
use IGK\System\Console\Logger;
use IGKSysUtil;

///<summary></summary>
/**
 * 
 * @package igk\io\GraphQl\System\Console\Commands
 */
class GraphQlInitSDLCommand extends AppExecCommand
{
	var $command = "--graphql:init-sdl";
	var $desc = "init controller sdl definition";
	var $category = "--graphql";
	// var $options=[];
	var $usage = 'controller';
	public function exec($command, ?string $controller = null)
	{ 

		require_once IGK_LIB_DIR.'/Lib/functions-helpers/db.php';
		$ctrl = self::GetController($controller);  
		echo GraphQlDbHelper::GenSQLDefinition($ctrl);

		
	}
}
