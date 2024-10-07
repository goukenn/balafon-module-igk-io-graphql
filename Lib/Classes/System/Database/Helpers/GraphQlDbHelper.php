<?php
// @author: C.A.D. BONDJE DOUE
// @file: GraphQlDbHelper.php
// @date: 20231015 23:44:01
namespace igk\io\GraphQl\System\Database\Helpers;

use IGK\Controllers\SysDbController; 
use igk\io\GraphQl\Schemas\GraphQlDefinitionBuilder;
use igk\io\GraphQl\Schemas\GraphQlFieldInfo;
use igk\io\GraphQl\Schemas\GraphQlType;
use IGK\Controllers\BaseController;
use igk\io\GraphQl\GraphQlException;
use igk\io\GraphQl\GraphQlQueryOptions;
use igk\io\GraphQl\Schemas\SchemaTypeDefinition;
use igk\io\GraphQl\System\Database\GraphQlDbTableResolver;
use IGK\Models\ModelBase;
use IGK\System\Console\Logger;
use IGK\System\Http\Request;
use IGK\System\IO\StringBuilder;
use IGKException;
use IGKSysUtil;
use IGKType;

///<summary></summary>
/**
 * 
 * @package igk\io\GraphQl\System\Database\Helpers
 */
class GraphQlDbHelper
{

	public static function InitMapKeys($info, ?BaseController $controller =null )
	{
		$ctrl = $controller ?? $info->controller;
		$v_use_cl_prefix = $ctrl === SysDbController::ctrl(); 
		$v_prefix = $info->prefix;
		// $n = IGKSysUtil::GetModelTypeNameFromInfo($info, $table);
		if (empty($v_prefix) && $v_use_cl_prefix) {
			$v_prefix = 'cl';
		}
		$keys = [];

		foreach ($info->columnInfo as $clInfo) {
			$tn = $clInfo->clName;
			$fn = ($v_prefix && igk_str_startwith($tn, $v_prefix)) ? substr($tn, strlen($v_prefix)) : $tn;


			$fieldName = lcfirst($fn);

			$keys[$clInfo->clName] = $fieldName;
		}

		return $keys;
	}
	public static function InitGraphDbQuery(BaseController $controller)
	{
		$query = [];
		igk_db_table_info($controller, function ($table, $info) use (&$query, $controller) {
			$n = IGKSysUtil::GetModelTypeNameFromInfo($info, $table);
			$key = 'ListOf' . $n;
			$query[$key] = (new GraphQlDbTableResolver($controller, $info, $key))->resolver();
		});
		return $query;
	}

	public static function BuildIntrospectSchema(BaseController $ctrl, $schema, $querydef = null)
	{
		$v_add = is_null($querydef);
		$v_queryobj = $querydef ?? SchemaTypeDefinition::CreateObject('Query');
		// $ct = $v_queryobj->addField('listOfUser')->listOf('User');
		$v_add ?? $schema->addType($v_queryobj);

		igk_db_table_info($ctrl, function ($table, $info) use ($schema, $ctrl, $v_queryobj, &$querydef) {
			Logger::info('builder : ' . $table);

			$v_use_cl_prefix = $ctrl === SysDbController::ctrl();
			$v_tdef = [];
			$v_prefix = $info->prefix;
			$n = IGKSysUtil::GetModelTypeNameFromInfo($info, $table);
			if (empty($v_prefix) && $v_use_cl_prefix) {
				$v_prefix = 'cl';
			}
			$querydef = $querydef ?? [];
			$fquery = 0;
			$def = SchemaTypeDefinition::CreateObject($n);
			$schema->addType($def);

			$v_queryobj->addField('ListOf' . ucfirst($n))->listOf($n);
			$schema->addType($v_queryobj);

			foreach ($info->columnInfo as $clInfo) {
				$tn = $clInfo->clName;
				$fn = ($v_prefix && igk_str_startwith($tn, $v_prefix)) ? substr($tn, strlen($v_prefix)) : $tn;

				if ($tn == 'Query') {
					$tn = 'actionQuery' . ($fquery ? '_' . $fquery : '');
					$fquery++;
				}
				$fieldName = lcfirst($fn);
				$type = GraphQlType::GetTypeFromDbColumnInfo($clInfo);
				SchemaTypeDefinition::CreateObject('OBJECT');

				$def->addField($fieldName)->scalar('String');

				// $schema->addType(); 

				// $def->type = 
				// $def->isRequired = true;
				// if ($clInfo->clNotNull) {
				// 	$def->isRequired = false;
				// }

				//if (($clInfo->clDefault || is_numeric($clInfo->clDefault)) && !DbColumnInfo::IsDbColumnInfoFunction($clInfo, $clInfo->clDefault)) {

				// $def->default = $clInfo->clDefault;
				//}

				// $v_tdef[] = $def;
			}
			// $gfields = new GraphQlFieldInfo;
			// $gfields->isArray = true;
			// $gfields->name = 'listOf' . $n;
			// $gfields->type = $n;
			// $gfields->args = ['id' => 'ID', 'limit' => 'Int', 'orderBy' => '[String]'];
			// $querydef[] = $gfields;
			// $builder->addType($n, $v_tdef);
			//
		});
	}
	/**
	 * generate GraphQL's SDL Definition
	 * @param BaseController $ctrl source controller 
	 * @return void 
	 * @throws IGKException 
	 */
	public static function GenSQLDefinition(BaseController $ctrl)
	{
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

	/**
	 * generate SDL function parameters
	 * @param array $parameters 
	 * @param array|Closure $parameters 
	 * @return void 
	 */
	public static function GenSDLFuncParameter(array $parameters, $isInjectTable=null){
		if (is_null($isInjectTable)){
			$isInjectTable = self::GetBaseInjectableParameterCallable();
		} else{ 
			if (is_array($isInjectTable)){
				$isInjectTable = function($c)use($isInjectTable){
					return is_array($c, $isInjectTable);
				};
			} else if (!($isInjectTable instanceof \Closure)){
				$isInjectTable = function(){return false;};
			}
		}

		$sb = new StringBuilder;
		$ch = '';
		$C_NUMBERS = ['Int','Float'];
		$v_mark_options = false;
		\IGK\System\Reflection\Helper\ReflectionHelper::GetParameterInfo($parameters, 
		function($name, $p)use(&$ch, $isInjectTable, $sb, $C_NUMBERS, & $v_mark_options){
			$t = $p->type;
			if ($t && $isInjectTable($t) ){
				if (!igk_is_class_assignable($t, ModelBase::class)){
					if (igk_is_class_assignable($t, GraphQlQueryOptions::class)){						
						$v_mark_options && igk_die_exception(GraphQlException::class, 'only single option is allowed');
						$v_mark_options = true;
					}
					return;
				}
				$t = 'ID';
			} else if (!$t){
				$t = 'String';
			} else {
				$t = self::GetScalarType($t);
			} 
			$sb->append($ch.$name.':');
			$sb->append($t);
			if ($v = igk_getv($p, 'default')){
				if (is_numeric($v) && in_array($t, $C_NUMBERS)){
					$v = floatval($v);
				}else{
					$v = igk_str_surround($v, '"');
				}
				$sb->append(' = '.$v);
			}
			$ch = ', ';
		});


		return $sb.'';
	}
	public static function GetScalarType(string $type){
		return igk_getv([
			'int'=>'Int',
			'float'=>'Float',
			'double'=>'Double',
			'array'=>'[String]'
		],strtolower($type), 'String');
	}
	public static function GetBaseInjectableParameterCallable(){
		return function($t){
			if (IGKType::IsPrimaryType($t)){
				return false;
			}
			return igk_is_class_assignable($t, GraphQlQueryOptions::class) || 
				igk_is_class_assignable($t, ModelBase::class)||
				igk_is_class_assignable($t, BaseController::class)||
				igk_is_class_assignable($t, Request::class)||
				igk_is_class_assignable($t, IInjectable::class);
		
		};
	}
}
