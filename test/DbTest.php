<?php

namespace Neoan3\Apps\test;
include __DIR__ . '/includes.php';

use Neoan3\Apps\Db;
use Neoan3\Apps\DbException;
use Neoan3\Apps\DbOOP;
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

    protected function setUp(): void
    {
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
        Db::debug();
        $try = Db::easy('user.id');
        $this->assertSame('SELECT `user`.`id` as "id" FROM `user`', $try['sql']);
    }

    /**
     * @throws DbException
     */
    public function testEasyWhere() {
        Db::debug();
        $try = Db::easy('user.id', ['^delete_date']);
        $this->assertStringContainsString('WHERE `delete_date` IS NULL', $try['sql']);

    }

    /**
     * @throws DbException
     */
    public function testEasySelectandi() {
        Db::debug();
        $try = Db::easy('#user.insert_date:inserted $user.id:someId');
        $this->assertStringContainsString('`insert_date`)*1000 as "inserted"', $try['sql']);
    }

    /**
     * @throws DbException
     */
    public function testEasyOperandi() {
        Db::debug();
        $try = Db::easy('user.id', [], ['limit' => [0, 1]]);
        $this->assertStringContainsString('LIMIT 0, 1', $try['sql']);
        $try = Db::easy('#user.insert_date:inserted $user.id:someId', ['id' => '$123']);
        $this->assertStringContainsString('HEX(`user`.`id`) as "someId"', $try['sql']);
        $this->assertStringContainsString('`id` = UNHEX(?)', $try['sql']);
    }

    /**
     * ASK block
     */

    /**
     * @throws DbException
     */
    public function testAskAnyFile() {
        Db::debug();
        Db::setEnvironment('file_location', '');
        $try = Db::ask('/test', ['key' => 1]);
        $this->assertStringContainsString('SELECT id as test', $try['sql']);
        $this->assertStringContainsString('WHERE id != ?', $try['sql']);
        $this->assertSame(1, $try['exclusions'][0]['value']);
    }

    /**
     * @throws DbException
     */
    public function testAskAnyInline() {
        Db::debug();
        $try = Db::ask('>SELECT id as test FROM user WHERE id != {{key}}', ['key' => 1]);
        $this->assertStringContainsString('SELECT id as test', $try['sql']);
        $this->assertStringContainsString('WHERE id != ?', $try['sql']);
        $this->assertSame(1, $try['exclusions'][0]['value']);
    }


    /**
     * @throws DbException
     */
    public function testAskUpdate() {
        Db::debug();
        $try = Db::ask('user', ['delete_date' => '.'], ['^delete_date']);
        $this->assertStringContainsString('WHERE `delete_date` IS NULL', $try['sql']);
        $this->assertStringContainsString('SET `delete_date` = NOW()', $try['sql']);
    }

    /**
     * @throws DbException
     */
    public function testUuidFail() {
        $this->expectException(DbException::class);
        $id = Db::uuid()->uuid;
    }


    /**
     * @throws DbException
     */
    public function testException() {
        $this->expectException(DbException::class);
        Db::setEnvironment('debug', false);
        db::easy('user.notset');
    }
    public function testOop()
    {
        $wrapper = new DbOOP(['debug' => true]);
        $c = $wrapper->easy('#sam.time:stamp');
        $this->assertSame('SELECT UNIX_TIMESTAMP(`sam`.`time`)*1000 as "stamp" FROM `sam`', $c['sql']);
    }



}
