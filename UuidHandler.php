<?php
/**
 * Created by PhpStorm.
 * User: sroehrl
 * Date: 1/24/2019
 * Time: 1:12 PM
 */

namespace Neoan3\Apps;

use mysqli_stmt;

class UuidHandler {
    public $uuid;

    /**
     * UuidHandler constructor.
     * @throws DbException
     */
    function __construct() {
        try {
            if (!$id = $this->newUuid()) {
                throw new DbException();
            }

        } catch (DbException $e) {
            DbOps::formatError(['connection'], 'Cannot create UUID: Connection failed.');
        } finally {
            return $this->newUuid();
        }
    }

    /**
     * @return $this
     * @throws DbException
     */
    public function newUuid(){
        $q = Db::query('SELECT REPLACE(UUID(),"-","") as id');

        while($row = $q['result']->fetch_assoc()){
            $this->uuid = strtoupper($row['id']);
        }
        return $this;
    }

    public function convertBinaryResults($resultArray){
        foreach ($resultArray as $i => $item){
            if(is_numeric($i)){
                $resultArray[$i] = $this->convertBinaryResults($item);
            } else {
                if(DbOps::isBinary($item)){
                    $resultArray[$i] = strtoupper(bin2hex($item));
                }
            }
        }
        return $resultArray;
    }

    public function convertToCompliantUuid($uuid=false){
        $id = ($uuid?$uuid:$this->uuid);
        $arr = [8,13,18,23];
        foreach ($arr as $part){
            $id = substr_replace($id, '-', $part, 0);
        }
        return $id;
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
