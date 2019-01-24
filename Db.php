<?php
namespace Neoan3\Apps;
/**
 * Class Db
 * @package Neoan3\Apps
 */
class Db
{
    /**
     * @var
     */
    private static $_db;
    /**
     * @var
     */
    private static $_connected;

    /**
     * @param $param1
     * @param null $param2
     * @param null $param3
     * @return array|int|mixed
     */
    public static function ask($param1, $param2 = null, $param3 = null) {
		if(is_array($param1)) {
			return self::smartSelect($param1, $param2, $param3);
		}
		if(substr($param1, 0, 1) == '/') {
			return self::smartQuery(substr($param1, 1), $param2);
		} elseif(substr($param1, 0, 1) == '?') {
			return self::smartSelect(substr($param1, 1), $param2, $param3);
		} else {
			if(is_array($param3)) {
				return self::smartUpdate($param1, $param2, $param3);
			} else {
				return self::smartInsert($param1, $param2);
			}
		}
	}

    /**
     * @param $selectorString
     * @param array $conditionArray
     * @param array $callFunctions
     * @param string $output
     * @return mixed
     */
    public static function easy($selectorString, $conditionArray=array(), $callFunctions=array(), $output='data'){
        $qStr = 'SELECT ';
        $i = 0;
        $selects = explode(' ',$selectorString);
        foreach ($selects as $what){
            $qStr .= ($i>0?', ':''). DbOps::selectandi($what);
            $i++;
        }
        $qStr .= ' FROM ';
        $remember = false;
        $joined = array();
        foreach ($selects as $what){
            $table = explode('.',trim($what));
            $table = preg_replace('/[^a-zA-Z_]/','',$table[0]);

            if($remember && $remember != $table && !in_array($table,$joined)){
                $qStr .= ' JOIN ' . $table . ' ON ' . $remember .'.id = '. $table.'.'.$remember.'_id ';
                array_push($joined,$table);
            } elseif(!$remember) {
                $qStr .= $table;
                $remember = $table;
            }
        }
        if(!empty($conditionArray)){
            $i = 0;
            foreach ($conditionArray as $key =>$value) {
                if(is_numeric($key)){
                    $key = substr($value,1);
                }
                $val = DbOps::operandi($value);
                $qStr .= ($i > 0 ? "  AND " : ' WHERE ') . $key .  $val;
                $i++;
            }
        }
        if(!empty($callFunctions)){
            foreach ($callFunctions as $callFunction=>$arguments){
                $qStr .= self::$callFunction($arguments) ."\n";
            }
        }
        if($output=='debug'){
            $output='callData';
        }
        $data = self::data($qStr);
        return $data[$output];
    }

    /**
     * @param $sql
     * @param string $type
     * @return array
     */
    public static function data($sql, $type = 'query') {
        if(defined('hard_debug')){
            var_dump($sql);
            die();
        }
		$data = self::query($sql);
		$return = array('callData' => array('sql' => $sql), 'data' => array());
		if($type == 'query' && is_object($data['result']) && $data['result']->num_rows > 0) {
			while($row = mysqli_fetch_assoc($data['result'])) {
				$return['data'][] = $row;
			}
		}
		if($type != 'query') {
			$return['callData']['rows'] = mysqli_affected_rows($data['link']);
			if($type == 'insert') {
				$return['callData']['lastId'] = mysqli_insert_id($data['link']);
			}
		} else {
			if(strtolower(substr(trim($sql), 0, 6)) == 'select') {
				$return['callData']['rows'] = mysqli_num_rows($data['result']);
			} else {
				$return['callData']['rows'] = mysqli_affected_rows($data['link']);
			}
		}
        if(defined('ask_debug')){
            var_dump($return);
            die();
        }

		return $return;
	}

    /**
     * @param $sql
     * @return array
     */
    public static function query($sql) {
		self::connect();
		$query = mysqli_query(self::$_db, $sql) or die(mysqli_error(self::$_db));
		return array('result' => $query, 'link' => self::$_db);
	}

    /**
     * @param $sql
     * @return array
     */
    public static function multi_query($sql) {
        self::connect();
        $query = mysqli_multi_query(self::$_db, $sql) or die(mysqli_error(self::$_db));
        return array('result' => $query, 'link' => self::$_db);
    }

    /**
     *
     */
    private static function connect() {
		if(!self::$_db) {
			self::$_db = mysqli_connect(db_host, db_user, db_password);
			mysqli_select_db(self::$_db, db_name);
			mysqli_set_charset(self::$_db, 'utf8');
        }
	}

    /**
     * @param $path
     * @param null $fields
     * @return array
     */
    private static function smartQuery($path, $fields = null) {
		$sql = file_get_contents(path . '/src/' . $path . '/' . $path . '.sql');
		if(!empty($fields)) {
			$sql = str_replace(array_map('self::curlyBraces', array_keys($fields)), array_values($fields), $sql);
		}
		return self::data($sql, 'query');
	}

    /**
     * @param $table
     * @param null $fields
     * @param null $where
     * @return mixed
     */
    private static function smartSelect($table, $fields = null, $where = null) {
		$additional = '';
		if(is_array($table)){
			$additional = DbCallFunctions::calls($table);
			$table = $table['from'];
		}

		$fieldsString = '';
		$i = 0;
		foreach($fields as $val) {
			$fieldsString .= ($i > 0 ? ",\n  " : '') . DbOps::selectandi($val);
			$i++;
		}
		$whereString = '';
		$i = 0;
		if(!empty($where)) {
			foreach($where as $key => $val) {
				$val = DbOps::operandi($val);
				$whereString .= ($i > 0 ? "\n  AND " : 'WHERE ') . $key .  $val;
				$i++;
			}
		}
		$whereString .= $additional;
		$array = array('table' => $table, 'fields_block' => $fieldsString, 'where_block' => $whereString);

		$sql = file_get_contents(dirname(__FILE__) . '/query/_query.sql');
		$processed = str_replace(array_map('self::curlyBraces', array_keys($array)), array_values($array), $sql);
		$data = self::data($processed, 'query');
		return $data['data'];
	}

    /**
     * @param $table
     * @param $fields
     * @param $where
     * @return int
     */
    private static function smartUpdate($table, $fields, $where) {
		$fieldsString = '';
		$i = 0;
		foreach($fields as $key => $val) {
			$fieldsString .= ($i > 0 ? ",\n  " : '') . $key . DbOps::operandi($val, true);
			$i++;
		}
		$whereString = '';
		$i = 0;
		foreach($where as $key => $val) {
			$val = DbOps::operandi($val);
			$whereString .= ($i > 0 ? "\n  AND " : '') . $key . $val;
			$i++;
		}
		$array = array('table' => $table, 'fields_block' => $fieldsString, 'where_block' => $whereString);

		$sql = file_get_contents(dirname(__FILE__) . '/query/_update.sql');
		$processed = str_replace(array_map('self::curlyBraces', array_keys($array)), array_values($array), $sql);
		$data = self::data($processed, 'update');
		return (int) $data['callData']['rows'];
	}

    /**
     * @param $table
     * @param $fields
     * @return int
     */
    private static function smartInsert($table, $fields) {
		$fieldsString = '';
		$i = 0;
		foreach($fields as $key => $val) {
			$fieldsString .= ($i > 0 ? ",\n  " : '') . $key . DbOps::operandi($val, true);
			$i++;
		}
		$array = array('table' => $table, 'fields_block' => $fieldsString);
		$sql = file_get_contents(dirname(__FILE__) . '/query/_insert.sql');
		$processed = str_replace(array_map('self::curlyBraces', array_keys($array)), array_values($array), $sql);
		$data = self::data($processed, 'insert');
		return (int) $data['callData']['lastId'];
	}


    /**
     * @param $inp
     * @return array|mixed
     */
    public static function escape($inp) {
		if(is_array($inp))
			return array_map(__METHOD__, $inp);

		if(!empty($inp) && is_string($inp)) {
			return str_replace(array('\\', "\0", "\n", "\r", "'", '"', "\x1a"), array('\\\\', '\\0', '\\n', '\\r', "\\'", '\\"', '\\Z'), $inp);
		}
		return $inp;
	}

    /**
     * @param $str
     * @return string
     */
    private static function curlyBraces($str) {
		return '{{' . $str . '}}';
	}

    /**
     * @param $info
     * @return bool
     */
    private static function formatError($info) {
		die('MYSQL: ' . $info);
	}


    /**
     * @param string $what
     */
    public static function debug($what='soft') {
        switch($what){
            case 'soft' : define('ask_debug',true);
                break;
            case 'hard' : define('hard_debug',true);
                break;
        }
    }

    /**
     * @param $json
     * @return string
     */
    public static function secureJson($json){
	    return '{ = "' . addslashes($json) . '" }';
    }
    public static function uuid(){
        return new UuidHandler();
    }



}