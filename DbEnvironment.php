<?php


namespace Neoan3\Apps;


use mysqli;

/**
 * Class DbEnvironment
 *
 * @package Neoan3\Apps
 */
class DbEnvironment
{
    /**
     * @var mysqli $_db
     */
    private $_db;

    /**
     * @var array
     */
    private $envVariables;


    /**
     * DbEnvironment constructor.
     *
     */
    function __construct()
    {
        $this->envVariables = [
            'db_app_root'          => defined('path') ? path : dirname(dirname(dirname(dirname(__FILE__)))),
            'db_host'              => defined('db_host') ? db_host : 'localhost',
            'db_name'              => defined('db_name') ? db_name : 'test',
            'db_user'              => defined('db_user') ? db_user : 'root',
            'db_password'          => defined('db_password') ? db_password : '',
            'db_assumes_uuid'      => defined('db_assumes_uuid') ? db_assumes_uuid : false,
            'db_file_location'     => defined('db_file_location') ? db_file_location : 'component',
            'db_dev_errors'        => defined('db_dev_errors') ? db_dev_errors : false,
            'db_charset'           => defined('db_charset') ? db_charset : 'utf8mb4',
            'db_filter_characters' => defined('db_filter_characters') ? db_filter_characters : '/[^a-zA-Z\_\^\.\s\*]/',
            'db_casing'            => defined('db_casing') ? db_casing : 'snake',
            'db_port'              => defined('db_port') ? db_port : 3306,
            'db_debug'             => defined('db_debug') ? db_debug : false
        ];
        return $this;
    }

    /**
     * @param $mysqli
     */
    public function bindMysqli($mysqli)
    {
        $this->_db = $mysqli;
    }

    /**
     * @param $charset
     *
     * @return object
     */
    public function setCharset($charset)
    {
        $this->_db->set_charset($charset);
        return $this->_db->get_charset();
    }

    /**
     * @param $property
     * @param $value
     */
    public function set($property, $value)
    {
        $this->envVariables['db_' . $property] = $value;
    }

    /**
     * Get environment variable
     *
     * @param $var
     *
     * @return mixed
     */
    public function get($var)
    {
        return $this->envVariables['db_' . $var];
    }

}
