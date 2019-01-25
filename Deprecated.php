<?php
/**
 * Created by PhpStorm.
 * User: sroehrl
 * Date: 1/25/2019
 * Time: 11:34 AM
 */

namespace Neoan3\Apps;


class Deprecated {
    static function data($sql, $type) {
        if(defined('hard_debug')){
            var_dump($sql);
            die();
        }
        $data = Db::query($sql);
        $return = array('callData' => array('sql' => $sql), 'data' => array());

        if($type == 'query' && is_object($data['result']) && $data['result']->num_rows > 0) {
            while ($row = $data['result']->fetch_assoc()){
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
}