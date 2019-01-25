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
		if(is_array($param1)){
            return self::smartSelect($param1, $param2, $param3);
        } else {
		    switch(substr($param1,0,1)){
                case '/': return self::smartQuery(substr($param1, 1), $param2);
                    break;
                case '?': return self::smartSelect(substr($param1, 1), $param2, $param3);
                    break;
                default:
                    if(is_array($param3)){
                        return self::smartUpdate($param1, $param2, $param3);
                    } else {
                        return self::smartInsert($param1, $param2);
                    }
            }
        }
	}
    private static function handleSelectandi($fields){
        $i = 0;
        $res = '';
        foreach ($fields as $what){
            $res .= ($i>0?', ':''). DbOps::selectandi($what);
            $i++;
        }
        return $res;
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
        $selects = explode(' ',$selectorString);
        $qStr .= self::handleSelectandi($selects);

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
            $qStr .= self::handleConditions($conditionArray);
        }
        if(!empty($callFunctions)){
            foreach ($callFunctions as $callFunction=>$arguments){
                $qStr .= self::$callFunction($arguments) ."\n";
            }
        }
        if($output=='debug'){
            return $qStr;
        }
        return self::handleResults($qStr);
    }
    private static function handleConditions($conditionArray){
        $return ='';
        $i = 0;
        foreach ($conditionArray as $key =>$value) {
            if(is_numeric($key)){
                $key = substr($value,1);
            }
            $val = DbOps::operandi($value,false,$key);
            $return .= ($i > 0 ? "  AND " : ' WHERE ') . $key .  $val;
            $i++;
        }
        return $return;
    }
    public static function handleResults($qStr){
        $result = [];
        if($exe =  self::preparedQuery($qStr)){
            while ($row = $exe->fetch_assoc()){
                $result[] = $row;
            }
            $exe->free();
            DbOps::clearExclusions();
        }
        return $result;
    }
    public static function prepareStmt($sql){
        $db = self::connect();
        return $db->prepare($sql);
    }
    public static function executeStmt($stmt,$types,$inserts){
        $stmt->bind_param($types,...$inserts);
        if(!$stmt->execute()){
            return false;
        }
        return $stmt->get_result();
    }
    public static function preparedQuery($sql){
        if(!empty($exclusions = DbOps::getExclusions())){
            $prepared = self::prepareStmt($sql);
            $inserts = [];
            $types = '';
            foreach ($exclusions as $i=> $substitute){
                $types .= $substitute['type'];
                $inserts[] = $substitute['value'];
            }
            return self::executeStmt($prepared,$types,$inserts);
        } else {
            return self::query($sql)['result'];
        }
    }

    /**
     * @param $sql
     * @param string $type
     * @return array
     */
    public static function data($sql, $type = 'query') {
        trigger_error('Deprecated function',E_USER_NOTICE);
        return Deprecated::data($sql,$type);
	}
	public static function raw(){
        return self::connect();
    }

    /**
     * @param $sql
     * @return array
     */
    public static function query($sql) {
		$db = self::connect();
		$query = $db->query($sql) or die(mysqli_error(self::$_db));
		return array('result' => $query, 'link' => $db);
	}

    /**
     * @param $sql
     * @return array
     */
    public static function multi_query($sql) {
        self::connect();
        $query = self::connect()->multi_query($sql) or die(mysqli_error(self::$_db));
        return array('result' => $query, 'link' => self::$_db);
    }

    /**
     *
     */
    private static function connect() {
		if(!self::$_db) {
			self::$_db = new \mysqli(db_host, db_user, db_password,db_name);
//			mysqli_select_db(self::$_db, db_name);
            self::$_db->set_charset('utf-8');
//			mysqli_set_charset(self::$_db, 'utf8');
        }
		return self::$_db;
	}

    /**
     * @param $path
     * @param null $fields
     * @return array
     */
    private static function smartQuery($path, $fields = null) {
        $parts = explode('/',$path);
        $file = isset($parts[1])?$parts[1]:$parts[0];
        $sql = file_get_contents(path . '/component/' . $parts[0] . '/' . $file . '.sql');
		if(!empty($fields)) {
            $sql = preg_replace_callback('/\{\{([a-zA-Z_]+)\}\}/',function($hit) use ($fields){
                DbOps::addExclusion($fields[$hit[1]],'s');
                return '?';
            },$sql);
		}
		return self::handleResults($sql);

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
		$fieldsString = self::handleSelectandi($fields);
		$whereString = self::handleConditions($where);
		$whereString .= $additional;
		$array = array('table' => $table, 'fields_block' => $fieldsString, 'where_block' => $whereString);
		$sql = Ops::embrace(file_get_contents(dirname(__FILE__) . '/query/_query.sql'),$array);
		return self::handleResults($sql);
	}

    /**
     * @param $table
     * @param $fields
     * @param $where
     * @return mixed
     */
    private static function smartUpdate($table, $fields, $where) {
		$fieldsString = self::handleSelectandi($fields);
		$whereString = self::handleConditions($where);
		$array = array('table' => $table, 'fields_block' => $fieldsString, 'where_block' => $whereString);
        $sql = Ops::embrace(file_get_contents(dirname(__FILE__) . '/query/_update.sql'),$array);
        return self::handleResults($sql);
	}

    /**
     * @param $table
     * @param $fields
     * @return mixed
     */
    private static function smartInsert($table, $fields) {
		$fieldsString = self::handleSelectandi($fields);
		$array = array('table' => $table, 'fields_block' => $fieldsString);
        $sql = Ops::embrace(file_get_contents(dirname(__FILE__) . '/query/_insert.sql'),$array);
        return self::handleResults($sql);
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

    /**
     * @return UuidHandler
     */
    public static function uuid(){
        return new UuidHandler();
    }

}