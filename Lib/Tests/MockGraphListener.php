<?php
// @author: C.A.D. BONDJE DOUE
// @file: MockGraphListener.php
// @date: 20221105 17:47:07
namespace igk\io\GraphQl\Tests;

use igk\io\GraphQl\GraphQlQueryOptions;
use igk\io\GraphQl\IGraphQlInspector;
use igk\io\GraphQl\IGraphQlMapDataResolver;
use IGK\Models\Users;
use IGKException;

///<summary></summary>
/**
* 
* @package IGK
*/
class MockGraphListener implements IGraphQlInspector, IGraphQlMapDataResolver{

    private $m_source;
    private $m_sourceType;

    public function checkIsInMutation(GraphQlQueryOptions $options=null){
        return [
            "type"=>$options->getContext() == 'mutation'
        ];
    }
    public function getSourceTypeName(): ?string
    {
        return $this->m_sourceType;
    }
    public function setSource($source){
        $this->m_source = $source;
    }
    public function user($id=null){ 
        return [
            "name"=>"user1",
            "email"=>"cbondje@igkdev.com",
            "lang"=>"en",
            "press"=>"pressing"
        ];
    }
    public function users($id=null){ 
        return [
            [
            "email"=>"cbondje@igkdev.com"
            ],[
            "email"=>"cbondje@igkdev.be"
            ]
        ];
    }
    public function picture(){ 
        return [
            "url"=>"https://com.test.balafon.get-picure/",
        ];
    }
    public function usersInject(?Users $user){
        if ($user) 
        return [$user];//->map(['clLogin'=>'email'])];
    }
    public function usersInjectArray($uid){
        $row1 = Users::createEmptyRow(); 
        $row3 = Users::createEmptyRow(); 
        $row1->clLogin = "dummy@test.com";
        $row3->clLogin = "vlam@test.com";
        return igk_getv([4=>[
            new Users($row1),
            new Users($row3),
        ]], $uid);
    }

    /**
     * get map data
     * @param string $typeName 
     * @return mixed 
     * @throws IGKException 
     */
    public function getMapData(string $typeName){
        return igk_getv(['Users'=>[
            'clLogin'=>'email',
            'clId'=>'id',
            'clLocale'=>'locale'
        ]], $typeName);
    }
    /**
     * call in mutation to update an user.
     * @param string $name 
     * @return string[] 
     */
    public function updateUser(string $name, GraphQlQueryOptions $options=null){
        
        return [
            "name"=>$name."_update", 
        ];
    }
    public function updateUserArray(string $name){
        igk_debug_wln('call :'.__METHOD__);
        return [
            ["name"=>$name."_update"],
            ["name"=>$name."_2update"],
        ];
    }
    /**
     * check with use injection
     * @param Users $user 
     * @param string $lang 
     * @return false|Users 
     */
    public function changeLang(?Users $user, $lang='fr'){
        if (!$user){
            return false;
        }
  
        $user->clLocale = $lang;
        $user->fb_user_id=null;
        if (!$user->save(true)){
            return false;
        }
        return $user;
    }
    /**
     * get source data
     * @return mixed 
     */
    public function query(){

        return $this->m_source ?? [             
            'locale'=>"en" ,
            'email'=>"t4@local.test" , 
            'lang'=>'en',
            'press'=>'pressing'           
        ];
    }
}