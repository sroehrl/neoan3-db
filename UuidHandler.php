<?php
/**
 * Created by PhpStorm.
 * User: sroehrl
 * Date: 1/24/2019
 * Time: 1:12 PM
 */

namespace Neoan3\Apps;


/**
 * Class UuidHandler
 *
 * @package Neoan3\Apps
 */
class UuidHandler
{
    /**
     * @var
     */
    public $uuid;
    /**
     * @var DbOps
     */
    static $_ops;

    /**
     * UuidHandler constructor.
     *
     * @param DbOps $ops
     *
     * @throws DbException
     */
    function __construct($ops)
    {
        self::$_ops = $ops;
        try {
            if (!$id = $this->newUuid()) {
                throw new DbException();
            }

        } catch (DbException $e) {
            self::$_ops->formatError(['connection'], 'Cannot create UUID: Connection failed.');
        } finally {
            return $this->newUuid();
        }
    }

    /**
     * Generates new UUID
     *
     * @return $this
     * @throws DbException
     */
    public function newUuid()
    {
        $q = Db::query('SELECT UPPER(REPLACE(UUID(),"-","")) as id');
        while ($row = $q['result']->fetch_object()) {
            $this->uuid = $row->id;
        }

        return $this;
    }

    /**
     * handles binary to hex conversion
     *
     * @param $resultArray
     *
     * @return mixed
     */
    public function convertBinaryResults($resultArray)
    {
        foreach ($resultArray as $i => $item) {
            if (is_numeric($i)) {
                $resultArray[$i] = $this->convertBinaryResults($item);
            } elseif (is_string($item)) {
                if (DbOps::isBinary($item)) {
                    $resultArray[$i] = strtoupper(bin2hex($item));
                }
            }
        }
        return $resultArray;
    }

    /**
     * Converts short UUIDs to RFC 4122 conform format
     *
     * @param bool $uuid
     *
     * @return bool|mixed
     */
    public function convertToCompliantUuid($uuid = false)
    {
        $id = ($uuid ? $uuid : $this->uuid);
        $arr = [8, 13, 18, 23];
        foreach ($arr as $part) {
            $id = substr_replace($id, '-', $part, 0);
        }
        return $id;
    }

    /**
     * Converts to binary
     *
     * @param bool $uuid
     *
     * @return string
     */
    public function insertAsBinary($uuid = false)
    {
        return '{ = ' . $this->unhexUuid($uuid) . ' }';
    }

    /**
     * converts from binary to hex
     *
     * @param bool $uuid
     *
     * @return string
     */
    private function unhexUuid($uuid = false)
    {

        return 'UNHEX("' . ($uuid ? $uuid : $this->uuid) . '")';
    }

    /**
     * @param bool $newUuid
     *
     * @return string
     */
    private function hexUuid($newUuid = false)
    {
        return 'HEX(' . ($newUuid ? 'UUID()' : '"' . $this->uuid . '"') . ')';
    }

}
