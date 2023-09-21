<?php
// @author: C.A.D. BONDJE DOUE
// @file: MockGraphListener.php
// @date: 20221105 17:47:07
namespace igk\io\GraphQl\Tests;

use igk\io\GraphQl\IGraphQlMapDataResolver;
use IGK\Models\Users;
use IGKException;

///<summary></summary>
/**
* 
* @package IGK
*/
class MockGraphListener implements IGraphQlMapDataResolver{
    public function user($id=null){ 
        return [
            "name"=>"user1",
            "email"=>"cbondje@igkdev.com"
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
    public function usersInject(Users $user){
        // return [$user->map(['clLogin'=>'email'])];
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
    public function updateUser(string $name){
        return [
            "name"=>$name."_update", 
        ];
    }
    public function updateUserArray(string $name){
        return [
            ["name"=>$name."_update"],
            ["name"=>$name."_2update"],
        ];
    }
    public function changeLang(Users $user, $lang='fr'){
  
        $user->clLocale = $lang;
        $user->fb_user_id=null;
        if (!$user->save(true)){
            return false;
        }
        return $user;
    }
    public function query(){
        return[             
            'locale'=>"en" ,
            'email'=>"t4@local.test" ,            
        ];
    }
}