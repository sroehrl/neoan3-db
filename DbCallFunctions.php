<?php
/**
 * Created by PhpStorm.
 * User: sroehrl
 * Date: 1/24/2019
 * Time: 1:00 PM
 */

namespace Neoan3\Apps;


use Countable;

/**
 * Class DbCallFunctions
 *
 * @package Neoan3\Apps
 */
class DbCallFunctions
{
    /**
     * @param $array
     *
     * @return string
     */
    static function calls($array)
    {
        $return = '';
        if (is_countable($array) && count($array) > 1) {
            array_shift($array);
            foreach ($array as $key => $value) {
                $func = $key;
                $return .= self::$func($value);
            }
        }
        return $return;
    }

    /**
     * @param $function
     * @param $arguments
     *
     * @return mixed
     */
    static function call($function, $arguments)
    {
        return self::$function($arguments);
    }

    /**
     * @param $array
     *
     * @return string
     */
    private static function orderBy($array)
    {
        $origin = $array;
        if (count($array) > 1) {
            self::calls(array_shift($array));
        }
        return ' ORDER BY ' . $origin[0] . ' ' . strtoupper($origin[1]);
    }

    /**
     * @param $array
     *
     * @return string
     */
    private static function groupBy($array)
    {
        $origin = $array;
        if (count($array) > 1) {
            self::calls(array_shift($array));
        }
        return ' GROUP BY ' . $origin[0] . (intval($origin[1]) > 0 ? ', ' . $origin[1] : '');
    }

    /**
     * @param $array
     *
     * @return string
     */
    private static function limit($array)
    {
        $origin = $array;
        if (count($array) > 1) {
            self::calls(array_shift($array));
        }
        return ' LIMIT ' . $origin[0] . (intval($origin[1]) > 0 ? ', ' . $origin[1] : '');
    }
}

// Polyfill for PHP<7.3
if (!function_exists('is_countable')) {
    function is_countable($var)
    {
        return (is_array($var) || $var instanceof Countable);
    }
}
