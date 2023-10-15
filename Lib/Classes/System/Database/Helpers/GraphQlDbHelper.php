<?php
// @author: C.A.D. BONDJE DOUE
// @file: GraphQlDbHelper.php
// @date: 20231015 23:44:01
namespace igk\io\GraphQl\System\Database\Helpers;

use IGK\Controllers\SysDbController;
use IGK\Database\DbColumnInfo;
use igk\io\GraphQl\Schemas\GraphQlDefinitionBuilder;
use igk\io\GraphQl\Schemas\GraphQlFieldInfo;
use igk\io\GraphQl\Schemas\GraphQlType;
use IGK\Controllers\BaseController;
use IGK\System\Console\Logger;
use IGKSysUtil;

///<summary></summary>
/**
* 
* @package igk\io\GraphQl\System\Database\Helpers
*/
class GraphQlDbHelper{
    public static function GenSQLDefinition(BaseController $ctrl){
        $builder = new GraphQLDefinitionBuilder;
		igk_db_table_info($ctrl, function ($table, $info) use ($builder, $ctrl, &$querydef) {
			Logger::info('builder : ' . $table);
			// $n = getTableName();
			$v_use_cl_prefix = $ctrl === SysDbController::ctrl();
			$v_tdef = [];
			$v_prefix = $info->prefix;
			$n = IGKSysUtil::GetModelTypeNameFromInfo($info, $table);
			if (empty($v_prefix) && $v_use_cl_prefix) {
				$v_prefix = 'cl';
			}
			$querydef = $querydef ?? [];
			$fquery = 0;
			foreach ($info->columnInfo as $clInfo) {
				$tn = $clInfo->clName;
				$fn = ($v_prefix && igk_str_startwith($tn, $v_prefix)) ? substr($tn, strlen($v_prefix)) : $tn;

				if ($tn == 'Query') {
					$tn = 'actionQuery' . ($fquery ? '_' . $fquery : '');
					$fquery++;
				}
				$def = new GraphQlFieldInfo;
				$def->name = lcfirst($fn);

				$def->type = GraphQlType::GetTypeFromDbColumnInfo($clInfo);
				$def->isRequired = true;
				if ($clInfo->clNotNull) {
					$def->isRequired = false;
				}

				//if (($clInfo->clDefault || is_numeric($clInfo->clDefault)) && !DbColumnInfo::IsDbColumnInfoFunction($clInfo, $clInfo->clDefault)) {

					// $def->default = $clInfo->clDefault;
				//}

				$v_tdef[] = $def;
			}
			$gfields = new GraphQlFieldInfo;
			$gfields->isArray = true;
			$gfields->name = 'listOf' . $n;
			$gfields->type = $n;
			$gfields->args = ['id' => 'ID', 'limit' => 'Int', 'orderBy' => '[String]'];
			$querydef[] = $gfields;
			$builder->addType($n, $v_tdef);
			//
		});
		$builder->addType('Query', $querydef);
		echo $builder->render();
    }
}