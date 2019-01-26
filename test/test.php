<?php
namespace Test;
define('path',dirname(dirname(__FILE__)));
define('db_host','localhost');
define('db_name','michael');
define('db_user','root');
define('db_password','');

require_once path . '/Db.php';
require_once path . '/DbOps.php';
require_once path . '/UuidHandler.php';
require_once path . '/Deprecated.php';

use Neoan3\Apps\Db;
$id = Db::uuid();
// debug
// db::debug();

// ask insert
/*$test = Db::ask('user',[
    'id'=>'$'.$id->uuid,
    'username'=>'neoan',
    'password'=>'123456'
]);*/

// ask update
/*$test = Db::ask('user',[
    'username'=>'neoan',
    'password'=>'123456'
],['password'=>123456]);*/

// ask select
//$test = Db::ask('?user',['username'],['password'=>123456]);

// easy
//$test = Db::easy('user.* $user.id:id',['password'=>'!']);

// data (deprecated)
/*$test = Db::data('SELECT * FROM user')['data'];
$test[0]['id'] = bin2hex($test[0]['id']);;*/

// manual prepared
/*$users = [
    ['tom','123456'],
    ['john','654321'],
    ['zach','32123'],
];
$id = Db::uuid();
$stmt = Db::prepareStmt('INSERT INTO user(id, username, password) VALUES(UNHEX(?),?,?) ');
foreach ($users as $user){
    array_unshift($user,$id->newUuid()->uuid);
    Db::executeStmt($stmt,'sss',$user);
}*/
$test = Db::easy('user.* $user.id:id');
var_dump($test);
die();