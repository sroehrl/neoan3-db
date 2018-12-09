<?php
namespace Neoan3\Apps;

class Db
{
	private static $_db;
	private static $_connected;

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
	public static function easy($selectorString,$conditionArray=array(),$callFunctions=array(),$output='data'){
        $qStr = 'SELECT ';
        $i = 0;
        $selects = explode(' ',$selectorString);
        foreach ($selects as $what){
            $qStr .= ($i>0?', ':''). self::selectandi($what);
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
                $val = self::operandi($value);
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

	public static function query($sql) {
		self::connect();
		$query = mysqli_query(self::$_db, $sql) or die(mysqli_error(self::$_db));
		return array('result' => $query, 'link' => self::$_db);
	}
    public static function multi_query($sql) {
        self::connect();
        $query = mysqli_multi_query(self::$_db, $sql) or die(mysqli_error(self::$_db));
        return array('result' => $query, 'link' => self::$_db);
    }

	private static function connect() {
		if(!self::$_db) {
			self::$_db = mysqli_connect(db_host, db_user, db_password);
			mysqli_select_db(self::$_db, db_name);
			mysqli_set_charset(self::$_db, 'utf8');
        }
	}

	private static function smartQuery($path, $fields = null) {
		$sql = file_get_contents(path . '/src/' . $path . '/' . $path . '.sql');
		if(!empty($fields)) {
			$sql = str_replace(array_map('self::curlyBraces', array_keys($fields)), array_values($fields), $sql);
		}
		return self::data($sql, 'query');
	}
	private static function smartSelect($table, $fields = null, $where = null) {
		$additional = '';
		if(is_array($table)){
			$additional = self::calls($table);
			$table = $table['from'];
		}

		$fieldsString = '';
		$i = 0;
		foreach($fields as $val) {
			$fieldsString .= ($i > 0 ? ",\n  " : '') . self::selectandi($val);
			$i++;
		}
		$whereString = '';
		$i = 0;
		if(!empty($where)) {
			foreach($where as $key => $val) {
				$val = self::operandi($val);
				$whereString .= ($i > 0 ? "\n  AND " : 'WHERE ') . $key .  $val;
				$i++;
			}
		}
		$whereString .= $additional;
		$array = array('table' => $table, 'fields_block' => $fieldsString, 'where_block' => $whereString);

		$sql = file_get_contents(neoan_path . '/apps/query/_query.sql');
		$processed = str_replace(array_map('self::curlyBraces', array_keys($array)), array_values($array), $sql);
		$data = self::data($processed, 'query');
		return $data['data'];
	}

	private static function smartUpdate($table, $fields, $where) {
		$fieldsString = '';
		$i = 0;
		foreach($fields as $key => $val) {
			$fieldsString .= ($i > 0 ? ",\n  " : '') . $key . self::operandi($val, true);
			$i++;
		}
		$whereString = '';
		$i = 0;
		foreach($where as $key => $val) {
			$val = self::operandi($val);
			$whereString .= ($i > 0 ? "\n  AND " : '') . $key . $val;
			$i++;
		}
		$array = array('table' => $table, 'fields_block' => $fieldsString, 'where_block' => $whereString);

		$sql = file_get_contents(neoan_path . '/apps/query/_update.sql');
		$processed = str_replace(array_map('self::curlyBraces', array_keys($array)), array_values($array), $sql);
		$data = self::data($processed, 'update');
		return (int) $data['callData']['rows'];
	}

	private static function smartInsert($table, $fields) {
		$fieldsString = '';
		$i = 0;
		foreach($fields as $key => $val) {
			$fieldsString .= ($i > 0 ? ",\n  " : '') . $key . self::operandi($val, true);
			$i++;
		}
		$array = array('table' => $table, 'fields_block' => $fieldsString);
		$sql = file_get_contents(neoan_path . '/apps/query/_insert.sql');
		$processed = str_replace(array_map('self::curlyBraces', array_keys($array)), array_values($array), $sql);
		$data = self::data($processed, 'insert');
		return (int) $data['callData']['lastId'];
	}
	private static function checkAs($rest){
        if(empty($rest) || $rest == '' || strpos($rest,'*')!== false){
            // catch asterix-selector
            return '';
        }
        $as = explode(':',$rest);
        $als = explode('.',$rest);
        if(count($as)>1){
            return ' as '.$as[1];
        } elseif (count($als)>1){
            return ' as '.$als[1];
        } else {

            return ' as '. preg_replace('/[^a-zA-Z_]/','',$rest);
        }
    }
    private static function cleanAs($rest){
        $as = explode(':',$rest);
        $als = explode('.',$rest);
        if(count($as)>1){
            return $as[0];
        } elseif (count($als)>1){
            return $als[0];
        } else {
            return $rest;
        }
    }
	private static function selectandi($string){
		$firstLetter = strtolower(substr($string, 0, 1));
		$rest = substr($string, 1);
		$return = '';
		switch($firstLetter) {
			case '#':
			    $return = 'UNIX_TIMESTAMP(' . self::cleanAs($rest) . ')*1000';
                break;
			default: $return = $string;
		}
		return $return . self::checkAs($string);
	}
	private static function operandi($string, $set = false) {
		if(empty($string) && $string !== "0") {
			return ($set ? ' = NULL' : ' IS NULL');
		}

		$firstLetter = strtolower(substr($string, 0, 1));
		$return = '';
		switch($firstLetter) {
			case '>':
			case '<':
				$return = ' ' . $firstLetter . ' "' . intval(substr($string, 1)) . '"';
				break;
			case '.':
				$return = ' = NOW()';
				break;
			case '!':
				$return = (strtolower($string) == '!null' || strlen($string) == 1 ? (!$set ? ' IS NOT NULL' : self::formatError('Cannot set "NOT NULL" as value for "' . substr($string, 1) . '"')) : ($set ? self::formatError('Cannot use "!= ' . substr($string, 1) . '" to set a value') : ' != "' . substr($string, 1) . '"'));
				break;
			case '{':
				$return = ' ' . substr($string, 1, -1);
				break;
			case 'n':
				$return = (strtolower($string) == 'null' ? ($set ? ' = NULL' : ' IS NULL') : ' = "' . self::escape($string) . '"');
				break;
            case '^':
                $return = ($set ? ' = NULL' : ' IS NULL');
                break;
			default:
				$return = ' = "' . self::escape($string) . '"';
				break;
		}
		return $return;
	}

	public static function escape($inp) {
		if(is_array($inp))
			return array_map(__METHOD__, $inp);

		if(!empty($inp) && is_string($inp)) {
			return str_replace(array('\\', "\0", "\n", "\r", "'", '"', "\x1a"), array('\\\\', '\\0', '\\n', '\\r', "\\'", '\\"', '\\Z'), $inp);
		}
		return $inp;
	}

	private static function curlyBraces($str) {
		return '{{' . $str . '}}';
	}

	private static function formatError($info) {
		die('MYSQL: ' . $info);
		return false;
	}
	private static function calls($array){
		if(count($array) > 1) {
			$trash = array_shift($array);
			foreach($array as $key => $value) {
				$func = $key;
				return self::$func($value);
			}
		}
		return '';
	}
    public static function debug($what='soft') {
        switch($what){
            case 'soft' : define('ask_debug',true);
                break;
            case 'hard' : define('hard_debug',true);
                break;
        }
    }
    public static function secureJson($json){
	    return '{ = "' . addslashes($json) . '" }';
    }
	//callfuncs

	static function orderBy($array) {
		$origin = $array;
		if(count($array) > 1) {
			self::calls(array_shift($array));
		}
		return ' ORDER BY ' . $origin[0] . ' ' . strtoupper($origin[1]);
	}
    static function groupBy($array) {
        $origin = $array;
        if(count($array) > 1) {
            self::calls(array_shift($array));
        }
        return ' GROUP BY ' . $origin[0] . (intval($origin[1])>0?', ' . $origin[1]:'');
    }
    static function limit($array) {
        $origin = $array;
        if(count($array) > 1) {
            self::calls(array_shift($array));
        }
		return ' LIMIT ' . $origin[0] . (intval($origin[1])>0?', ' . $origin[1]:'');
    }
}