<?php
// @author: C.A.D. BONDJE DOUE
// @file: ProjectBuilder.php
// @date: 20231016 08:30:28
namespace igk\io\GraphQl\System\TamTam\Plugins;

use IGK\Controllers\BaseController;
use IGK\Helper\ViewHelper;
use igk\io\GraphQl\GraphQlConstants;
use igk\io\GraphQl\Helper\GraphQlReaderUtils;
use igk\io\GraphQl\Schemas\GraphQlProjectDbIntrospection;
use igk\io\GraphQl\Schemas\GraphQlProjectDbSchemaDefinitionBase;
use igk\io\GraphQl\Schemas\IGraphQlIntrospection;
use igk\io\GraphQl\System\Actions\GraphQlProjectEndPointActionBase;
use igk\io\GraphQl\System\Database\Helpers\GraphQlDbHelper;
use IGK\System\Console\Logger;
use IGK\System\Exceptions\ArgumentTypeNotValidException;
use IGK\System\IO\File\PHPScriptBuilder;
use IGK\System\IO\Path;
use IGK\System\TamTam\Plugins\ProjectBuilderPluginBase;
use igk\io\GraphQl\Schemas\Traits\IntropectionSourceTypeNameTrait;
use IGKException;
use ReflectionException;

///<summary></summary>
/**
* Project Builder 
* @package igk\io\GraphQl\System\TamTam\Plugins
*/
class ProjectBuilder extends ProjectBuilderPluginBase{
    /**
     * build sdl file
     * @var mixed
     */
    var $SDLFile;

    /**
     * schema configuration 
     * @var mixed
     */
    var $schemaFile;


    /**
     * define introspection class to use. if not specified the system search for
     * GraphQl/SDL/Instrospection
     * @var ?string
     */
    var $introspectionClass;

   
    /**
     * 
     * @param BaseController $ctrl 
     * @return void 
     */ 
    public function build(BaseController $ctrl){
        
        $this->genAction($ctrl);
        $this->genSDL($ctrl); 


        $balafon = $this->getCLIService();
        $v_schema = $this->schemaFile ?? "GraphQl/schema.json";
        $v_intro_class = $this->introspectionClass ?? \GraphQl\SDL\Introspection::class;
        if (1 || !($cl = $ctrl->resolveClass($v_intro_class))){ 
            $balafon->makeProjectClass($ctrl, $v_intro_class, [
                'uses'=>[
                    IntropectionSourceTypeNameTrait::class,
                    GraphQlReaderUtils::class,
                    ViewHelper::class,
                    GraphQlDbHelper::class
                ],
                'implements'=>[
                    IGraphQlIntrospection::class
                ],
                'defs'=>implode("\n",[
                    'use IntropectionSourceTypeNameTrait;', 
                    'public function query() { ',
                    '    return GraphQlDbHelper::InitGraphDbQuery(ViewHelper::CurrentCtrl());',
                    '}',
                ])
            ]); 
            $cl = $ctrl->resolveClass($v_intro_class) ?? igk_die("failed to make the class"); 
        }
        // gen service 
        
        $v_schema_class = $v_intro_class.GraphQlConstants::SCHEMAS_CLASS_DEF_SUFFIX; //  ?? \GraphQl\SDL\Introspection::class;
        if (1 || !($ctrl->resolveClass($v_schema_class))){ 
            $balafon->makeProjectClass($ctrl, $v_schema_class, [
                'uses'=>[
               
                ],
                'extends'=>GraphQlProjectDbSchemaDefinitionBase::class,
                'implements'=>[
                ],
            ]); 
            $ctrl->resolveClass($v_schema_class) ?? igk_die("failed to make the class"); 
        }
        $p = GraphQlReaderUtils::GetIntropectionSchema($cl, $ctrl); 
        $v_schema = Path::Combine($ctrl->getDataDir(), $v_schema);
        Logger::info('generate : '.$v_schema);
        igk_io_w2file($v_schema, $p);
    } 
    protected function genSDL(BaseController $ctrl){
        $v_sdl = $this->SDLFile ?? 'GraphQl/sdl.gql';
        ob_start();
        GraphQlDbHelper::GenSQLDefinition($ctrl);
        $p = ob_get_contents();
        ob_end_clean();
        $v_sdl = Path::Combine($ctrl->getDataDir(), $v_sdl);
        Logger::info('generate : '.$v_sdl);
        igk_io_w2file($v_sdl, $p);
    }
    /**
     * generate action 
     * @param BaseController $ctrl 
     * @return void 
     * @throws IGKException 
     * @throws ArgumentTypeNotValidException 
     * @throws ReflectionException 
     */
    protected function genAction(BaseController $ctrl){
        $action = \Actions::class.'\\EndPointAction';
        $balafon = $this->getCLIService();
        if (!($ctrl->resolveClass($action))){ 
            $balafon->makeProjectClass($ctrl, $action, [ 
                'extends'=>GraphQlProjectEndPointActionBase::class, 
            ]); 
            $ctrl->resolveClass($action) ?? igk_die("failed to make action class the class"); 
        }  
    }
}

 
 