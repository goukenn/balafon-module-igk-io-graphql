<?php
// @author: C.A.D. BONDJE DOUE
// @file: GraphQlProjectEndPointActionBase.php
// @date: 20231016 11:13:24
namespace igk\io\GraphQl\System\Actions;

use Exception;
use IGK\Actions\ActionBase;
use igk\io\GraphQl\GraphQlException;
use igk\io\GraphQl\GraphQlParser;
use IGK\System\Http\ErrorRequestResponse;
use IGK\System\Http\ExceptionErrorRequestResponse;
use IGK\System\Http\Request;
use IGK\System\Http\WebResponse;

///<summary></summary>
/**
 * 
 * @package igk\io\GraphQl\System\Actions
 */
abstract class GraphQlProjectEndPointActionBase extends ActionBase
{
    protected function die(string $message, $code = 500)
    {
        $ex = new GraphQlException($message, $code);
        igk_do_response(new ExceptionErrorRequestResponse($ex));
    }
    public function Users(Request $request)
    {
        // igk_wln_die("handling user not alload ... no parameter call index.");
        return $this->index_post($request, __FUNCTION__);
    }
    /**
     * graph ql end point
     * @return void 
     */
    public function index_post(Request $request, ?string $sourceType)
    {
        $this->getController()->getConfigs('graphql/');
        $clname = \GraphQl\SDL\Introspection::class;
        $ctrl = $this->getController();
        $query = $request->getJsonData();
        // + | --------------------------------------------------------------------
        // + | query test
        // + |

        // $query = <<<EOF
        // {
        //     ListOfMessages{
        //         message
        //     }
        // }
        // EOF;

        // + | --------------------------------------------------------------------
        // + | introspection test 
        // + |

        // $query = <<<EOF
        // query IntrospectionQuery{
        //     __schema{
        //         message
        //     }
        // }
        // EOF;

        if (empty($query)) {
            $this->die('Operation not allowed');
        }
        ($cl = $ctrl->resolveClass($clname)) || $this->die('Missing Instrospection class');
        $listener = new $cl();
        $listener->setSourceTypeName($sourceType);
        $r = null; 
        try {
            $r = GraphQlParser::Parse($query, $listener, $reader);
            if ($reader->getIntrospected()) {
                return WebResponse::Create('json', $r);
            }
        } catch (Exception $ex) {
            $this->die('parsing failed : ' . $ex->getMessage(), 500);
        }
        return WebResponse::Create('json', json_encode(['data' => $r]));
    }
}
