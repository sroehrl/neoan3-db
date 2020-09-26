<?php
namespace Test;
define('path',dirname(dirname(__FILE__)));
define('db_host','localhost');
define('db_name', 'test');
define('db_user','root');
define('db_password','');

//optional
define('db_assumes_uuid',true);
define('db_dev_errors', true);

require_once path . '/DbOps.php';
require_once path . '/Db.php';
require_once path . '/UuidHandler.php';
require_once path . '/Deprecated.php';
require_once path . '/DbException.php';
require_once path . '/DbEnvironment.php';
require_once path . '/Db.php';

use Neoan3\Apps\Db;
use Neoan3\Apps\DbException;
use Neoan3\Apps\DbOps;

try {
    $id = Db::uuid();
} catch (DbException $e) {
    var_dump($e->getMessage());
    die();
}


var_dump($id->uuid);
die();
