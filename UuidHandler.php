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
        $this->uuid = Db::data('SELECT REPLACE(UUID(),"-","") as id')['data'][0]['id'];

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