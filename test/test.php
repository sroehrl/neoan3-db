<?php
namespace Test;

use Neoan3\Apps\Db;
use Neoan3\Apps\DbOOP;

require_once '../vendor/autoload.php';

const path = __DIR__;

/*Db::setEnvironment([
    'host' => 'localhost',
    'assumes_uuid' => true,
    'name' => 'test',
    'app_root' => __DIR__
]);*/

$db = new DbOOP([
    'host' => 'localhost',
    'assumes_uuid' => true,
    'name' => 'test',
    'app_root' => __DIR__
]);
// insert
Db::debug();
$test = $db->smart('user',[
    'email'=>'adam@mail.com',
    'some' => 0,
    'last' => null,
    'empty' => ''
]);
//$test = $db->easy('user.*',['^password']);


var_dump($test);

