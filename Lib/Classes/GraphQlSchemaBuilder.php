<?php
// @author: C.A.D. BONDJE DOUE
// @file: GraphQLSchemaBuilder.php
// @date: 20231005 22:03:59
namespace igk\io\GraphQl;

use IGK\Controllers\BaseController;
use IGK\Controllers\SysDbController;
use IGK\Database\DbColumnInfo;
use igk\io\GraphQl\Schemas\GraphQlDefinitionBuilder;
use igk\io\GraphQl\Schemas\GraphQlFieldInfo;
use igk\io\GraphQl\Schemas\GraphQlType;
use IGK\System\Console\Logger;

use IGKSysUtil as sysutil;

///<summary></summary>
/**
 * 
 * @package igk\io\GraphQl
 */
class GraphQLSchemaBuilder
{
    public function __construct()
    {
    }
    private function _build(BaseController $ctrl, $callback)
    {
        $db = $ctrl->getDataAdapter();
        if (!$ctrl->getUseDataSchema()) {
            if (
                !empty($table = $ctrl->getDataTableName()) &&
                ($info = $ctrl->getDataTableInfo()) &&
                $db && $db->connect()
            ) {
                $table = igk_db_get_table_name($table, $ctrl);
                $callback($table, $info);
                $db->close();
            }
        } else {
            $tb = $ctrl::loadDataFromSchemas();
            if (($db && $db->connect())) {
                $v_tblist = [];
                if ($tables = igk_getv($tb, "tables")) {
                    foreach (array_keys($tables) as $k) {
                        $v_tblist[$k] = $k;
                        $callback($k, $tables[$k]);
                    }
                }
                $db->close();
            }
        }
    }

    public function generate(BaseController $ctrl): string
    { 
        $ctrl = $ctrl; // SysDbController::ctrl(); // igk_bondje::ctrl();
        $ctrl->register_autoload();
        $builder = new GraphQlDefinitionBuilder;
        $this->_build($ctrl, function ($table, $info) use ($builder) {
            Logger::info('builder : ' . $table);
            // $n = getTableName();
            $v_tdef = [];
            $v_prefix = $info->prefix;
            $n = sysutil::GetModelTypeNameFromInfo($info, $table);

            foreach ($info->columnInfo as $clInfo) {
                $tn = $clInfo->clName;
                $fn = ($v_prefix && igk_str_startwith($tn, $v_prefix)) ? substr($tn, strlen($v_prefix)) : $tn;
                $def = new GraphQlFieldInfo;
                $def->name = $fn;

                $def->type = GraphQlType::GetTypeFromDbColumnInfo($clInfo);
                $def->isRequired = true;
                if (($clInfo->clDefault || is_numeric($clInfo->clDefault)) && !DbColumnInfo::IsDbColumnInfoFunction($clInfo, $clInfo->clDefault)) {

                    $def->default = $clInfo->clDefault;
                }

                $v_tdef[] = $def;
            }
            $builder->addType($n, $v_tdef);
        });
        return $builder->render();
    }
}
