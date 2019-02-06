<?php
/**
 * Created by PhpStorm.
 * User: sroehrl
 * Date: 1/24/2019
 * Time: 11:39 AM
 */

namespace Neoan3\Apps;

/**
 * Class DbOps
 * @package Neoan3\Apps
 */
class DbOps {
    /**
     * @var array
     */
    private static $preparedExclusions = [];

    /**
     * @return array
     */
    static function getExclusions(){
        return self::$preparedExclusions;
    }

    /**
     *
     */
    static function clearExclusions(){
        self::$preparedExclusions = [];
    }

    /**
     * @param $value
     * @param $type
     */
    static function addExclusion($value, $type){
        self::$preparedExclusions[] = self::prepareBinding($value,$type);
    }

    /**
     * @param $value
     * @param $type
     * @return array
     */
    static function prepareBinding($value, $type){
        return ['type'=>$type,'value'=>$value];
    }
    /**
     * @param $string
     * @param bool $set
     * @param bool $prepared
     * @return array|bool|string
     */
    static function operandi($string, $set = false, $prepared = false) {
        if(empty($string) && $string !== "0") {
            return ($set ? ' = NULL' : ' IS NULL');
        }

        $firstLetter = strtolower(substr($string, 0, 1));
        switch($firstLetter) {
            case '>':
            case '<':
                $return = ' ' . $firstLetter . ' "' . intval(substr($string, 1)) . '"';
                break;
            case '.':
                $return = ' = NOW()';
                break;
            case '$':
                $rest = substr($string, 1);
                if($prepared){
                    $return = ' = UNHEX(?)';
                    self::addExclusion($rest,'s');
                } else {
                    $return = ' = UNHEX("'.$rest.'")';
                }
                break;
            case '!':
                if(strtolower($string) == '!null' || strlen($string) == 1){
                    if($set){
                        self::formatError('Cannot set "NOT NULL" as value for "' . substr($string, 1) . '"');
                    }
                    $return = ' IS NOT NULL ';
                } else {
                    if($set){
                        self::formatError('Cannot use "!= ' . substr($string, 1) . '" to set a value');
                    }
                    $return = ' != "' . substr($string, 1) . '"';
                }
                break;
            case '{':
                $return = ' ' . substr($string, 1, -1);
                break;
            case '^':
                $return = ($set ? ' = NULL' : ' IS NULL');
                break;
            default:
                if(strtolower($string) == 'null'){
                    $return = ($set ? ' = NULL' : ' IS NULL');
                } elseif($prepared){
                    $return = ' = ? ';
                    self::addExclusion($string,'s');
                } else {
                    $return = ' = "'. Db::escape($string) . '"';
                }
                break;
        }
        return $return;
    }

    /**
     * @param $string
     * @return string
     */
    static function selectandi($string){
        $firstLetter = strtolower(substr($string, 0, 1));
        $rest = substr($string, 1);
        switch($firstLetter) {
            case '#':
                $return = 'UNIX_TIMESTAMP(' . self::cleanAs($rest) . ')*1000';
                break;
            case '$':
                $return = 'HEX(' . self::cleanAs($rest) . ')';
                break;
            default:
                $return = $string;
        }
        return $return . self::checkAs($string);
    }
    /**
     * @param $rest
     * @return string
     */
    static function checkAs($rest){
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

    /**
     * @param $rest
     * @return mixed
     */
    static function cleanAs($rest){
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
    /**
     * @param $info
     * @return bool
     */
    static function formatError($info) {
        die('MYSQL: ' . $info);
    }

    /**
     * @param $str
     * @return bool
     */
    static function isBinary($str) {
        return preg_match('~[^\x20-\x7E\t\r\n]~', $str) > 0;
    }
}
