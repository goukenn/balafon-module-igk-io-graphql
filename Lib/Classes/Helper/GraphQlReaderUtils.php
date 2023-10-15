<?php
// @author: C.A.D. BONDJE DOUE
// @file: GraphQlReaderUtils.php
// @date: 20231013 08:37:05
namespace igk\io\GraphQl\Helper;

use Closure;
use IGK\Helper\Activator;
use igk\io\GraphQl\Annotations\Mutation;
use igk\io\GraphQl\GraphQlConstants;
use igk\io\GraphQl\GraphQlParser;
use igk\io\GraphQl\GraphQlPropertyInfo;
use igk\io\GraphQl\GraphQlReaderConstants;
use igk\io\GraphQl\GraphQlReadSectionInfo;
use igk\io\GraphQl\IGraphQlDescribedProperty;
use IGK\System\IO\StringBuilder;
use igk\io\GraphQl\GraphQlReferenceArrayObject;
use igk\io\GraphQl\Schemas\GraphQlSchemaDefinitionTypeBase;
use igk\io\GraphQl\Schemas\SchemaTypeDefinition;
use igk\io\GraphQl\Schemas\ServeSchema;
use IGK\System\Helpers\AnnotationHelper;
use ReflectionMethod;

///<summary></summary>
/**
 * 
 * @package igk\io\GraphQl\Helper
 */
abstract class GraphQlReaderUtils
{

    public static function InitSchemaDefinition($schema_builder, ServeSchema $schema)
    {
        $j = new $schema_builder();
        $j->buildSchema($schema);
    }

    public static function GetIntropectionSchema($object_class, $request_schema)
    {
        $cl = get_class($object_class);
        $schema = new ServeSchema;
        $schema->addType(SchemaTypeDefinition::CreateScalar('String')); //, 'String Scalar definition'));
        $schema->addType(SchemaTypeDefinition::CreateScalar('Int', 'Int Scalar definition'));
        $schema->addType(SchemaTypeDefinition::CreateScalar('ID', 'ID Scalar definition'));

        $obj = SchemaTypeDefinition::CreateObject('User');
        $obj->addField('name')->scalar('String');
        $obj->addField('field')->scalar('String');
        $schema->addType($obj);

        $v_queryobj = SchemaTypeDefinition::CreateObject('Query');
        $ct = $v_queryobj->addField('listOfUser')->listOf('User');
        $schema->addType($v_queryobj);

        $mutations = null;
        $chains = [];
        $uses = null;
        if ($cl) {

            $v_definition_class = $cl . GraphQlConstants::SCHEMAS_CLASS_DEF_SUFFIX;
            if (class_exists($v_definition_class) && is_subclass_of($v_definition_class, GraphQlSchemaDefinitionTypeBase::class)) {
                self::InitSchemaDefinition($v_definition_class, $schema);
            }



            foreach (get_class_methods($cl) as $m) {
                if ($m == 'query') continue;

                $p = new ReflectionMethod($cl, $m);
                $b = AnnotationHelper::GetMethodAnnotations($p, $uses);
                if (!$b) continue;

                foreach ($b as $k => $v) {
                    if ($v instanceof Mutation) {
                        $mutations = $mutations ?? SchemaTypeDefinition::CreateObject('Mutation');
                        $chains['mutations'] = $mutations;
                        $nv = $v->name ?? $m;
                        $mutations->addField($m)
                        ->setAlias($v->name)
                        ->listOf('User');
                    }
                }
            }
        }
        foreach ($chains as $c) {
            $schema->addType($c);
        }
        $schema->setMutationTypeName('Mutation');
        return $schema->render();
    }
    /**
     * 
     * @param mixed $variables 
     * @param mixed $context_variable 
     * @return array 
     */
    public static function MergeVariableToExport($variables, $context_variable)
    {
        $tvar = [];
        $var = $variables;
        $gargs = (array)$context_variable;
        if ($var) {
            foreach ($var as $t => $tt) {
                if (igk_key_exists($gargs, $n_arg = '$' . $t)) {
                    unset($gargs[$n_arg]);
                }
                $tvar[$t] = $tt;
            }
        }
        while ($gargs && (count($gargs) > 0)) {
            $k = array_key_first($gargs);
            $v = array_shift($gargs);
            if ($k[0] == '$') {
                $k = substr($k, 1);
                $tvar[$k] = $v->default;
            }
        }
        return $tvar; // ->argument;
    }
    public static function InvokeListenerMethod($reader, $def, $v_core_data = null, $mapping = null, $type_call = 'query')
    {

        return GraphQlReadSectionInfo::InvokeListenerMethod($reader, $def, $v_core_data, $mapping, $type_call);
    }
    public static function GetReservedValue(GraphQlPropertyInfo $property)
    {
        $n = $property->name;
        switch ($n) {
            case '__typename':
                return $property->section->getSourceTypeName();
                break;
        }
    }
    public static function CheckSectionBrank($tokenid, &$end, &$brank)
    {
        switch ($tokenid) {
            case GraphQlReaderConstants::T_READ_START:
                $brank++;
                break;
            case GraphQlReaderConstants::T_READ_END:
                $brank--;
                if ($brank == 0) {
                    $end = true;
                }
                break;
        }
    }
    public static function BindComment(GraphQlParser $parser, $v)
    {
        $options_defs = $options_defs ?? implode('|', array_keys(Activator::GetClassVar(get_class($parser))));
        if ($c = preg_match_all("/@=\{(?P<n>" . $options_defs . ")}($|\s)/", $v, $tab)) {
            $i = 0;
            while ($c > 0) {
                $n = $tab['n'][$i];
                $parser->$n = !$parser->$n; // toggle options
                $c--;
                $i++;
            }
        }
    }

    public static function RenderPropertiesDefinition($prop, $showDescription = true)
    {
        //render properties
        $sb = new StringBuilder;
        foreach ($prop as $k => $v) {
            $tb = [];
            if ($showDescription && ($v instanceof IGraphQlDescribedProperty)) {
                if ($desc = $v->description) {
                    $sb->appendLine(implode("\n", ['"""', $desc, '"""']));
                }
            }
            if ($v instanceof GraphQlPropertyInfo) {
                if ($v->alias) {
                    $tb[] = $v->alias . ':';
                }
                $tb[] = $k;



                // get definitions
                // type definition
            }
            if ($tb) {
                $sb->appendLine(implode(' ', $tb));
            }
        }
        return $sb . '';
    }
    public static function ReplaceArrayDef(&$refObj, $key, $outdata)
    {
        if ($refObj instanceof GraphQlReferenceArrayObject) {
            $refObj->replaceWith($outdata, $key);
            return;
        }
        $pos = $pos ?? array_search($key, array_keys($refObj));
        $t3 = array_slice($refObj, 0, $pos + 1, true) + $outdata +  array_slice($refObj, $pos, count($refObj) - $pos, true);
        unset($t3[$key]);
        $refObj = $t3;
    }

    /**
     * 
     * @param mixed $prop 
     * @param mixed $default 
     * @return array 
     */
    public static function InitDefaultProperties(?array $prop, $default = null)
    {
        $v_root = [];
        $tq = [['n' => &$v_root, 'ref' => $prop]];
        while (count($tq) > 0) {
            $q = array_shift($tq);
            $n = &$q['n'];
            $p = $q['ref'] ?? [];
            while (count($p) > 0) {
                $v = array_shift($p);
                if (is_string($v)) {
                    continue;
                }

                !($v instanceof GraphQlPropertyInfo) && igk_die('not a graphql properties definitions');

                if ($v->hasChild) {
                    $no = [];
                    $n[$v->getKey()] = &$no;
                    $data = $v->getChildSection()->getRefPointer()->getRefData();

                    array_unshift($tq, ['n' => &$no, 'ref' => $data]);
                    unset($no);
                } else {
                    $n[$v->getKey()] = !$v->optional ?  $default : $v->default;
                }
            }
        }
        return $v_root;
    }
}
