<?php
/**
 * Created by PhpStorm.
 * User: sroehrl
 * Date: 1/24/2019
 * Time: 1:12 PM
 */

namespace Neoan3\Apps;


class UuidHandler {
    public $uuid;
    function __construct() {
        return $this->newUuid();
    }

    public function newUuid(){
        $q = Db::query('SELECT REPLACE(UUID(),"-","") as id');

        while($row = $q['result']->fetch_assoc()){
            $this->uuid = $row['id'];
        }
        return $this;
    }
    public function insertAsBinary($uuid=false){
        return '{ = '.$this->unhexUuid($uuid).' }';
    }
    private function unhexUuid($uuid=false){

        return 'UNHEX("'.($uuid?$uuid:$this->uuid).'")';
    }
    private function hexUuid($newUuid=false){
        return  'HEX('.($newUuid?'UUID()':'"'.$this->uuid.'"').')';
    }
}