<?php

namespace Neoan3\Apps;

use mysqli;
use mysqli_sql_exception;
use mysqli_stmt;

/**
 * Class Db
 * The following variables as used and required
 * db_host (e.g. localhost)
 * db_user (e.g. root)
 * db_name (e.g. my_db)
 * db_password (e.g. WSLDOH32hj)
 * db_app_root (e.g. __DIR__)
 *
 * The following variables are optional:
 * db_assumes_uuid (if defined && true, will assume BINARY(16) id-fields and react accordingly)
 * db_dev_errors (if defined && true, will output used SQl in errors & exceptions)
 * db_file_location (if defined, will overwrite the default "component"- expectation for SQL-files)
 * db_charset (if defined, will overwrite the default "utf8mb4" assumption)
 * db_filter_characters (if defined, will overwrite the default value "/[^a-zA-Z\_\^\.\s]/" )
 * db_casing (if defined, will overwrite the default value "snake" (currently accepts "camel")
 *
 * @package Neoan3\Apps
 */
class Db extends DbOps
{
    /**
     * @var
     */
    private static $_db;
    /**
     * @var DbEnvironment
     */
    private static $_env;
    /**
     * @var DbOps
     */
    private static $_ops;

    /**
     * Db initiator.
     */
    private static function init()
    {
        if (!self::$_env) {
            self::$_env = new DbEnvironment();
        }
        if (!self::$_ops) {
            self::$_ops = new DbOps(self::$_env);
        }
    }

    /**
     * @param $methodName
     * @param $arguments
     *
     * @return array|int|mixed
     * @throws DbException
     */
    public static function __callStatic($methodName, $arguments)
    {
        return self::ask($methodName, ...$arguments);
    }

    /**
     * @param      $param1
     * @param null $param2
     * @param null $param3
     *
     * @return array|int|mixed
     * @throws DbException
     */
    public static function ask($param1, $param2 = null, $param3 = null)
    {
        self::init();
        if (is_array($param1)) {
            return self::smartSelect($param1, $param2, $param3);
        } else {
            switch (substr($param1, 0, 1)) {
                case '>':
                case '/':
                    return self::smartQuery($param1, $param2);
                    break;
                case '?':
                    return self::smartSelect(substr($param1, 1), $param2, $param3);
                    break;
                default:
                    if (is_array($param3)) {
                        return self::smartUpdate($param1, $param2, $param3);
                    } else {
                        return self::smartInsert($param1, $param2);
                    }
            }
        }
    }

    /**
     * @param      $table
     * @param      $id
     * @param bool $hard
     *
     * @return array|int|mixed
     * @throws DbException
     */
    public static function delete($table, $id, $hard = false)
    {
        self::init();
        $table = self::$_ops->addBackticks(self::sanitizeKey($table));
        if ($hard) {
            $sql = '>DELETE FROM ' . $table . ' WHERE `id` =';
        } else {
            $deleteDate = (self::$_env->get('casing') == 'snake' ? 'delete_date' : 'deleteDate');
            $sql = '>UPDATE ' . $table . ' SET `' . $deleteDate . '` = NOW() WHERE `id` =';
        }
        if (self::$_env->get('assumes_uuid')) {
            $sql .= 'UNHEX({{id}})';
        } else {
            $sql .= '{{id}}';
        }
        return db::ask($sql, ['id' => $id]);
    }

    /**
     * @param $fields
     *
     * @return string
     */
    private static function handleSelectandi($fields)
    {
        $i = 0;
        $res = '';
        foreach ($fields as $what) {
            $res .= ($i > 0 ? ', ' : '') . self::$_ops->selectandi($what);
            $i++;
        }
        return $res;
    }

    /**
     * @param        $selectorString
     * @param array  $conditionArray
     * @param array  $callFunctions
     * @param string $output
     *
     * @return mixed
     * @throws DbException
     */
    public static function easy($selectorString, $conditionArray = [], $callFunctions = [], $output = 'data')
    {
        self::init();
        $qStr = 'SELECT ';
        $selects = explode(' ', $selectorString);
        $qStr .= self::handleSelectandi($selects);

        $qStr .= ' FROM ';
        $remember = false;
        $joined = [];
        foreach ($selects as $what) {
            $table = explode('.', trim($what));
            $table = preg_replace('/[^a-zA-Z_]/', '', $table[0]);
            $table = self::$_ops->addBackticks($table);
            if ($remember && $remember != $table && !in_array($table, $joined)) {
                $qStr .= ' JOIN ' . $table . ' ON ' . $remember . '.`id` = ' . $table . '.' . substr($remember, 0, -1) .
                         (self::$_env->get('casing') == 'snake' ? '_id` ' : 'Id` ');
                array_push($joined, $table);
            } elseif (!$remember) {
                $qStr .= $table;
                $remember = $table;
            }
        }
        if (!empty($conditionArray)) {
            $qStr .= self::handleConditions($conditionArray);
        }
        if (!empty($callFunctions)) {
            foreach ($callFunctions as $callFunction => $arguments) {
                $qStr .= DbCallFunctions::call($callFunction, $arguments) . "\n";
            }
        }
        if ($output == 'debug') {
            return $qStr;
        }
        return self::handleResults($qStr);
    }


    /**
     * @param $conditionArray
     *
     * @return string
     * @throws DbException
     */
    private static function handleConditions($conditionArray)
    {
        $return = '';
        $i = 0;
        foreach ($conditionArray as $key => $value) {
            if (is_numeric($key)) {
                $key = self::$_ops->addBackticks(substr($value, 1));
            }
            $key = self::sanitizeKey($key);
            $val = self::$_ops->operandi($value, false, $key);
            $return .= ($i > 0 ? "  AND " : ' WHERE ') . self::$_ops->addBackticks($key) . $val;
            $i++;
        }
        return $return;
    }

    /**
     * @param $qStr
     *
     * @return array|int
     * @throws DbException
     */
    public static function handleResults($qStr)
    {
        self::init();
        if (self::$_env->get('debug')) {
            $exclusions = self::$_ops->getExclusions();
            self::$_ops->clearExclusions();
            return [
                'sql'        => $qStr,
                'exclusions' => $exclusions
            ];
        }
        $result = 0;
        if ($exe = self::preparedQuery($qStr)) {
            if ($exe['result'] && !is_bool($exe['result'])) {
                $result = [];
                while ($row = $exe['result']->fetch_assoc()) {
                    $result[] = $row;
                }
                $exe['result']->free();
            } elseif (!$exe['result']) {
                $result = $exe;
            }
        }
        self::$_ops->clearExclusions();
        if (!self::$_env->get('assumes_uuid')) {
            if (self::$_db->insert_id > 0) {
                return self::$_db->insert_id;
            }
        } elseif (!empty($result)) {
            $handler = new UuidHandler(self::$_ops);
            $result = $handler->convertBinaryResults($result);
        }
        if (self::$_db->affected_rows > 0 && empty($result)) {
            return self::$_db->affected_rows;
        }
        return $result;
    }

    /**
     * @param $sql
     *
     * @return mysqli_stmt
     * @throws DbException
     */
    public static function prepareStmt($sql)
    {
        self::init();
        $db = self::connect();
        return $db->prepare($sql);
    }

    /**
     * @param mysqli_stmt $stmt
     * @param             $types
     * @param             $inserts
     *
     * @return array
     * @throws DbException
     */
    public static function executeStmt($stmt, $types, $inserts)
    {
        self::init();
        try {
            if (!$stmt) {
                throw new DbException('Statement not established');
            } elseif (!$stmt->bind_param($types, ...$inserts)) {
                throw new DbException('Binding error');
            }
            $stmt->execute();
        } catch (DbException $e) {
            self::$_ops->formatError($inserts, $e->getMessage());
        } finally {
            return self::evaluateQuery($stmt);
        }

    }

    /**
     * @param $sql
     *
     * @return array
     * @throws DbException
     */
    public static function preparedQuery($sql)
    {
        self::init();
        if (!empty($exclusions = self::$_ops->getExclusions())) {
            $prepared = self::prepareStmt($sql);
            $inserts = [];
            $types = '';
            foreach ($exclusions as $i => $substitute) {
                $types .= $substitute['type'];
                $inserts[] = $substitute['value'];
            }
            return self::executeStmt($prepared, $types, $inserts);
        } else {
            return self::query($sql);
        }
    }

    /**
     * @param mysqli_stmt $resObj
     *
     * @return array
     * @throws DbException
     */
    private static function evaluateQuery($resObj)
    {
        if (!$resObj) {
            throw new DbException('Unable to evaluate results');
        }
        return [
            'result'        => $resObj->get_result(),
            'affected_rows' => $resObj->affected_rows,
            'insert_id'     => $resObj->num_rows,
            'errno'         => $resObj->errno
        ];
    }

    /**
     * DEPRECATED
     *
     * Use ask-syntax instead
     *
     * @param        $sql
     * @param string $type
     *
     * @return array
     */
    public static function data($sql, $type = 'query')
    {
        self::init();
        self::deprecationWarning();
        return Deprecated::data($sql, $type);
    }

    /**
     * @return mysqli
     * @throws DbException
     */
    public static function raw()
    {
        self::init();
        return self::connect();
    }

    /**
     * @param $sql
     *
     * @return array|void
     * @throws DbException
     */
    public static function query($sql)
    {
        self::init();
        try {
            $db = self::connect();
            if (is_array($db)) {
                throw new DbException('Connection error');
            }
            if (!$query = $db->query($sql)) {
                throw new DbException('Failed to execute query!');
            }
        } catch (mysqli_sql_exception $e) {
            self::$_ops->formatError([], $e->getMessage(), $sql);

        } catch (DbException $e) {
            self::$_ops->formatError([], $e->getMessage(), $sql);
        }
        if (isset($query) && isset($db)) {
            return ['result' => $query, 'link' => $db];
        }
    }

    /**
     * @param $sql
     *
     * @return array
     * @throws DbException
     */
    public static function multi_query($sql)
    {
        self::init();
        self::connect();
        mysqli_report(MYSQLI_REPORT_STRICT);
        try {
            $query = self::connect()
                         ->multi_query($sql);
        } catch (mysqli_sql_exception $e) {
            throw $e;
        }
        return ['result' => $query, 'link' => self::$_db];
    }

    /**
     * @throws DbException
     */
    private static function connect()
    {
        mysqli_report(MYSQLI_REPORT_STRICT);

        if (!self::$_db) {
            try {
                self::$_db =
                    new mysqli(self::$_env->get('host'), self::$_env->get('user'), self::$_env->get('password'),
                        self::$_env->get('name'), self::$_env->get('port'));
            } catch (mysqli_sql_exception $e) {
                self::$_ops->formatError(['****'], 'Check defines! Can\'t connect to db');
            }
            self::$_env->bindMysqli(self::$_db);
        }
        return self::$_db;

    }

    /**
     * @param $charset
     *
     * @throws DbException
     */
    public static function setCharSet($charset)
    {
        self::init();
        if (!self::$_db) {
            self::connect();
        }
        self::$_env->setCharset($charset);
    }

    /**
     * set Environment variable(s)
     *
     * @param array|string $property
     * @param bool|string  $value
     *
     * @throws DbException
     */
    public static function setEnvironment($property, $value = false)
    {
        self::init();
        self::$_db = null;
        if (is_array($property)) {
            foreach ($property as $prop => $val) {
                self::$_env->set($prop, $val);
            }
        } elseif ($value !== false) {
            self::$_env->set($property, $value);
        } else {
            throw new DbException('setEnvironment is not set properly.');
        }
    }

    /**
     * @param      $path
     * @param null $fields
     *
     * @return array
     * @throws DbException
     */
    private static function smartQuery($path, $fields = null)
    {
        $root = self::$_env->get('app_root');
        $rest = substr($path, 1);
        if (substr($path, 0, 1) == '>') {
            $sql = $rest;
        } else {
            $parts = explode('/', $rest);
            $file = isset($parts[1]) ? $parts[1] : $parts[0];
            $filePath = '/' . self::$_env->get('file_location') . '/';
            $filePath .= $parts[0] . '/' . $file . '.sql';
            $sql = file_get_contents($root . $filePath);
        }
        if (!empty($fields)) {
            $sql = preg_replace_callback('/\{\{([a-zA-Z_]+)\}\}/', function ($hit) use ($fields) {
                if (!isset($fields[$hit[1]])) {
                    self::$_ops->formatError($hit, 'Required field missing: ' . $hit[1]);
                }
                self::$_ops->addExclusion($fields[$hit[1]]);
                return '?';
            }, $sql);
        }
        return self::handleResults($sql);

    }

    /**
     * @param      $table
     * @param null $fields
     * @param null $where
     *
     * @return mixed
     * @throws DbException
     */
    private static function smartSelect($table, $fields = null, $where = null)
    {
        $additional = '';
        if (is_array($table)) {
            $additional = DbCallFunctions::calls($table);
            $table = $table['from'];
        }
        $table = self::sanitizeKey($table);
        $fieldsString = self::handleSelectandi($fields);
        $whereString = !empty($where) ? self::handleConditions($where) : '';
        $whereString .= $additional;
        $sql = 'SELECT ' . $fieldsString . ' FROM ' . self::$_ops->addBackticks($table) . ' ' . $whereString;
        return self::handleResults($sql);
    }

    /**
     * @param $table
     * @param $fields
     * @param $where
     *
     * @return mixed
     * @throws DbException
     */
    private static function smartUpdate($table, $fields, $where)
    {
        $table = self::sanitizeKey($table);
        $fieldsString = self::setFields($fields);
        $whereString = self::handleConditions($where);
        $sql = 'UPDATE ' . self::$_ops->addBackticks($table) . ' SET ' . $fieldsString . $whereString;

        return self::handleResults($sql);
    }

    /**
     * @param $table
     * @param $fields
     *
     * @return mixed
     * @throws DbException
     */
    private static function smartInsert($table, $fields)
    {
        $table = self::sanitizeKey($table);
        if (!isset($fields['id']) && self::$_env->get('assumes_uuid')) {
            $fields['id'] = self::uuid()
                                ->insertAsBinary();
        }
        $fieldsString = self::setFields($fields);
        $sql = 'INSERT INTO ' . self::$_ops->addBackticks($table) . ' SET ' . $fieldsString;
        return self::handleResults($sql);
    }

    /**
     * @param $fields
     *
     * @return string
     * @throws DbException
     */
    private static function setFields($fields)
    {
        $fieldsString = '';
        $i = 0;
        foreach ($fields as $key => $val) {
            $fieldsString .= ($i > 0 ? ",\n  " : '') . self::$_ops->addBackticks($key) .
                             self::$_ops->operandi($val, true, true);
            $i++;
        }
        return $fieldsString;
    }

    /**
     * Sanitizes table-names and keys
     *
     * Default executed regex is [^a-zA-Z\_\^\.\s] and can be modified through
     * DbEnvironment::set('filter_characters',$regex)
     *
     * @param $keyString
     *
     * @return string|string[]|null
     */
    public static function sanitizeKey($keyString)
    {
        $pattern = self::$_env->get('filter_characters');
        return preg_replace($pattern, '', $keyString);
    }

    /**
     * DEPRECATED
     *
     * NeoanPHP2.x users: handling JSON&HTML escaping now completely moved out of DB-app
     * in favor of a single-responsibility approach
     *
     * @param $inp
     *
     * @return array|mixed
     */
    public static function escape($inp)
    {
        self::init();
        self::deprecationWarning();
        return Deprecated::escape($inp);
    }


    /**
     * Creates a NOTICE
     */
    private static function deprecationWarning()
    {
        $caller = next(debug_backtrace());
        $msg = 'Deprecated Db-function in function ' . $caller['function'] . ' called from ' . $caller['file'];
        $msg .= ' on line ' . $caller['line'];
        trigger_error($msg, E_USER_NOTICE);
    }


    /**
     * Sets debugging to highest mode: query will not be executed
     */
    public static function debug()
    {
        self::init();
        self::$_env->set('debug', true);
    }

    /**
     * @param $json
     *
     * @return string
     */
    public static function secureJson($json)
    {
        self::init();
        return '{ = "' . addslashes($json) . '" }';
    }

    /**
     * @return UuidHandler
     * @throws DbException
     */
    public static function uuid()
    {
        self::init();
        return new UuidHandler(self::$_ops);
    }

}
