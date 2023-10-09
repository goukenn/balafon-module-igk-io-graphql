<?php
// @author: C.A.D. BONDJE DOUE
// @file: GraphQlChainGraph.php
// @date: 20231009 09:41:59
namespace igk\io\GraphQl;
use igk\io\GraphQl\GraphQlParser as gParser;


///<summary></summary>
/**
* graph reading chain section 
* @package igk\io\GraphQl
*/
class GraphQlChainGraph{
    private $m_chainList;
    private $m_parent;
    public function __construct()
    {
        $this->m_chainList = [];
        $this->m_parent = null;
    }
    public function update($id, $v, & $chain_start){
        $chain_args = & $this->m_chainList;
        $p = & $this->m_parent;
        switch($id){
            case gParser::T_GRAPH_START:
                if ($chain_start){
                    // + | append entry fields
                    $chain_args[] = null;
                }
                $chain_start = 1;
                break;
            case gParser::T_GRAPH_END:
                if (empty($chain_args)){
                    // + | section pop 
                    if ($p){
                        $chain_args = array_pop($p);
                        break;
                    }
                } 
                // + || prop arg entry
                array_pop($chain_args);
                break;
            case gParser::T_GRAPH_NAME: 
                if ($chain_start){
                    // + | replace last argument 
                    array_pop($chain_args);
                    $chain_args[] = $v;
                }
                break;
            case gParser::T_GRAPH_ARGUMENT:
                // + | remove last argument from chain list 
                array_pop($chain_args);                
                // start new reading section 
                $p[] = $chain_args;
                $chain_args = [];
                break;

        }
    }
    /**
     * retrieve path
     * @return string 
     */
    public function path():string{
        return implode('/', $this->m_chainList);
    }
    /**
     * get base path to request 
     * @return null|string 
     */
    public function basePath():?string{
        if ($this->m_chainList){
            $p =  dirname($this->path());
            if ($p!='.') return $p;
        }
        return null;
    }
}