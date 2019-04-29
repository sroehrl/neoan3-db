<?php

namespace Neoan3\Apps\test;
include __DIR__ . '/includes.php';

use Neoan3\Apps\Db;
use Neoan3\Apps\DbException;
use PHPUnit\Framework\TestCase;

/**
 * Class DbTest
 * @package Neoan3\Apps\test
 */
class DbTest extends TestCase {

    /**
     * DbTest constructor.
     * @throws DbException
     */
    public function __construct() {
        parent::__construct();
        $expected = ['name' => 'db_app', 'assumes_uuid' => true, 'dev_errors' => true];
        Db::setEnvironment($expected);
    }

    /**
     * EASY block
     */

    /**
     * @throws DbException
     */
    public function testEasySimple() {
        $try = Db::easy('user.id');
        $this->assertArrayHasKey(0, $try);
    }

    /**
     * @throws DbException
     */
    public function testEasyWhere() {
        $try = Db::easy('user.id', ['^delete_date']);
        $this->assertTrue(
            empty($try) || isset($try[0])
        );
    }

    /**
     * @throws DbException
     */
    public function testEasySelectandi() {
        $try = Db::easy('#user.insert_date:inserted $user.id:someId');
        $this->assertTrue(isset($try[0]['inserted']));
    }

    /**
     * @throws DbException
     */
    public function testEasyOperandi() {
        $data = Db::easy('user.id', [], ['limit' => [0, 1]]);
        $this->assertTrue(isset($data[0]), 'Did not grab any user');
        $try = Db::easy('#user.insert_date:inserted $user.id:someId', ['id' => '$' . $data[0]['id']]);
        $this->assertTrue(isset($try[0]['inserted']));
    }

    /**
     * ASK block
     */

    /**
     * @throws DbException
     */
    public function testAskAnyFile() {
        Db::setEnvironment('file_location', '');
        $try = Db::ask('/test', ['key' => 1]);
        $this->assertTrue(isset($try[0]['test']));
    }

    /**
     * @throws DbException
     */
    public function testAskAnyInline() {
        $try = Db::ask('>SELECT id as test FROM user WHERE id != {{key}}', ['key' => 1]);
        $this->assertTrue(isset($try[0]['test']));
    }


    /**
     * @throws DbException
     */
    public function testAskUpdate() {
        $try = Db::ask('user', ['delete_date' => '.'], ['^delete_date']);
        $this->assertIsInt($try);
    }

    /**
     * @throws DbException
     */
    public function testUuid() {
        $id = Db::uuid()->uuid;
        $this->assertStringMatchesFormat('%x', $id);
    }

    /**
     * @throws DbException
     */
    public function testException() {
        $this->expectException(DbException::class);
        db::easy('user.notset');
    }

    public function testCallFunctionsLimit() {
        $try = Db::easy('user.id', ['^delete_date'], ['limit' => [0, 1]]);
        $this->assertTrue(
            empty($try) || isset($try[0])
        );
    }

}
