<?php
/**
 * Created by PhpStorm.
 * User: sroehrl
 * Date: 1/25/2019
 * Time: 11:34 AM
 */

namespace Neoan3\Apps;


class Deprecated
{
    static function data($sql, $type)
    {
        if (defined('hard_debug')) {
            var_dump($sql);
            die();
        }
        $data = Db::query($sql);
        $return = ['callData' => ['sql' => $sql], 'data' => []];

        if ($type == 'query' && is_object($data['result']) && $data['result']->num_rows > 0) {
            while ($row = $data['result']->fetch_assoc()) {
                $return['data'][] = $row;
            }
        }
        if ($type != 'query') {
            $return['callData']['rows'] = mysqli_affected_rows($data['link']);
            if ($type == 'insert') {
                $return['callData']['lastId'] = mysqli_insert_id($data['link']);
            }
        } else {
            if (strtolower(substr(trim($sql), 0, 6)) == 'select') {
                $return['callData']['rows'] = mysqli_num_rows($data['result']);
            } else {
                $return['callData']['rows'] = mysqli_affected_rows($data['link']);
            }
        }
        if (defined('ask_debug')) {
            var_dump($return);
            die();
        }

        return $return;
    }

    /**
     * @param $inp
     *
     * @return array|mixed
     */
    public static function escape($inp)
    {
        if (is_array($inp)) {
            return array_map(__METHOD__, $inp);
        }

        if (!empty($inp) && is_string($inp)) {
            return str_replace(['\\', "\0", "\n", "\r", "'", '"', "\x1a"],
                ['\\\\', '\\0', '\\n', '\\r', "\\'", '\\"', '\\Z'], $inp);
        }
        return $inp;
    }
}
