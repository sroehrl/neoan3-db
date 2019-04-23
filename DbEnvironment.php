<?php


namespace Neoan3\Apps;


use mysqli;

/**
 * Class DbEnvironment
 *
 * @package Neoan3\Apps
 */
class DbEnvironment {
    /**
     * @var mysqli $_db
     */
    private $_db;

    private $envVariables;

    /**
     * DbEnvironment constructor.
     *
     */
    function __construct() {
        $this->envVariables = [
            'db_host'                   => defined('db_host') ? db_host : 'localhost',
            'db_name'                   => defined('db_name') ? db_name : 'test',
            'db_user'                   => defined('db_user') ? db_user : 'root',
            'db_password'               => defined('db_password') ? db_password : '',
            'db_assumes_uuid'           => defined('db_assumes_uuid') ? db_assumes_uuid : false,
            'db_file_location'          => defined('db_file_location') ? db_file_location : 'component',
            'db_dev_errors'             => defined('db_dev_errors') ? db_dev_errors : false,
            'db_charset'                => defined('db_charset') ? db_charset : 'utf8mb4',
            'db_allowed_key_characters' => defined(
                'db_allowed_key_characters'
            ) ? db_allowed_key_characters : '/[^a-zA-Z\_\^\.\s]/',
        ];
        return $this;
    }

    function bindMysqli($mysqli) {
        $this->_db = $mysqli;
    }

    /**
     * @param $charset
     *
     * @return object
     */
    function setCharset($charset) {
        $this->_db->set_charset($charset);
        return $this->_db->get_charset();
    }

    function set($property, $value) {
        $this->envVariables['db_' . $property] = $value;
    }

    function get($var) {
        return $this->envVariables['db_' . $var];
    }

}
