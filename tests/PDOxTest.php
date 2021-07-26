<?php

namespace Jinya\Tests;

use Jinya\PDOx\Exceptions\InvalidQueryException;
use Jinya\PDOx\Exceptions\NoResultException;
use Jinya\PDOx\PDOx;
use Laminas\Hydrator\Strategy\BooleanStrategy;
use PDOException;
use PHPUnit\Framework\TestCase;
use function _HumbugBoxfd814575fcc2\RingCentral\Psr7\str;

class TestClassForTestFetchObjectWithoutHydrator
{
    public int $pkey;
}

class TestClassForTestFetchObjectWithoutHydratorWithInvalidPrototype
{
    public string $test;
}

class TestClassForTestFetchObjectWithHydrator
{
    public int $pkeyId;
}

class TestClassForTestFetchObjectWithHydratorAndStrategies
{
    public int $pkeyId;
    public bool $active;
}

class TestClassForTestFetchObjectWithHydratorWithInvalidPrototype
{
    public string $testField;
}

class PDOxTest extends TestCase
{

    public function test__construct(): void
    {
        $pdo = new PDOx('sqlite::memory:');
        $pdo->exec('CREATE TABLE test (pkey int primary key)');
        $pdo->exec('INSERT INTO test (pkey) VALUES (1)');
        $stmt = $pdo->query('SELECT * FROM test');

        $this->assertNotFalse($stmt);

        if ($stmt !== false) {
            $result = $stmt->fetch(PDOx::FETCH_ASSOC);
            $this->assertNotFalse($result);

            if ($result !== false) {
                $this->assertArrayHasKey('pkey', $result);
                $this->assertEquals(1, $result['pkey']);
            }
        }
    }

    public function test__constructDontUseHydrator(): void
    {
        $pdo = new PDOx('sqlite::memory:', options: [PDOx::PDOX_NAMING_UNDERSCORE_TO_CAMELCASE => false]);
        $pdo->exec('CREATE TABLE test (pkey int primary key)');
        $pdo->exec('INSERT INTO test (pkey) VALUES (1)');
        $stmt = $pdo->query('SELECT * FROM test');

        $this->assertNotFalse($stmt);

        if ($stmt !== false) {
            $result = $stmt->fetch(PDOx::FETCH_ASSOC);
            $this->assertNotFalse($result);

            if ($result !== false) {
                $this->assertArrayHasKey('pkey', $result);
                $this->assertEquals(1, $result['pkey']);
            }
        }
    }

    public function testFetchObjectWithoutHydrator(): void
    {
        $pdo = new PDOx('sqlite::memory:', options: [PDOx::PDOX_NAMING_UNDERSCORE_TO_CAMELCASE => false]);
        $pdo->exec('CREATE TABLE test (pkey int primary key)');
        $pdo->exec('INSERT INTO test (pkey) VALUES (1)');

        $data = $pdo->fetchObject('SELECT * FROM test', new TestClassForTestFetchObjectWithoutHydrator());

        $this->assertNotNull($data);
        $this->assertEquals(1, $data->pkey);
    }

    public function testFetchObjectWithoutHydratorMultipleEntries(): void
    {
        $this->expectException(InvalidQueryException::class);
        $this->expectExceptionMessage('Query returned more than one result');
        $pdo = new PDOx('sqlite::memory:', options: [PDOx::PDOX_NAMING_UNDERSCORE_TO_CAMELCASE => false]);
        $pdo->exec('CREATE TABLE test (pkey int primary key)');
        $pdo->exec('INSERT INTO test (pkey) VALUES (1)');
        $pdo->exec('INSERT INTO test (pkey) VALUES (2)');

        $pdo->fetchObject('SELECT * FROM test', new TestClassForTestFetchObjectWithoutHydrator());
    }

    public function testFetchObjectWithoutHydratorNoResultNull(): void
    {
        $pdo = new PDOx('sqlite::memory:', options: [PDOx::PDOX_NAMING_UNDERSCORE_TO_CAMELCASE => false]);
        $pdo->exec('CREATE TABLE test (pkey int primary key)');
        $pdo->exec('INSERT INTO test (pkey) VALUES (1)');
        $pdo->exec('INSERT INTO test (pkey) VALUES (2)');

        $result = $pdo->fetchObject('SELECT * FROM test WHERE pkey > 4', new TestClassForTestFetchObjectWithoutHydrator());
        $this->assertNull($result);
    }

    public function testFetchObjectWithoutHydratorNoResultException(): void
    {
        $this->expectException(NoResultException::class);
        $this->expectExceptionMessage('Query returned no result');
        $pdo = new PDOx('sqlite::memory:', options: [PDOx::PDOX_NAMING_UNDERSCORE_TO_CAMELCASE => false, PDOx::PDOX_NO_RESULT_BEHAVIOR => PDOx::PDOX_NO_RESULT_BEHAVIOR_EXCEPTION]);
        $pdo->exec('CREATE TABLE test (pkey int primary key)');
        $pdo->exec('INSERT INTO test (pkey) VALUES (1)');
        $pdo->exec('INSERT INTO test (pkey) VALUES (2)');

        $pdo->fetchObject('SELECT * FROM test WHERE pkey > 4', new TestClassForTestFetchObjectWithoutHydrator());
    }

    public function testFetchObjectWithoutHydratorWithInvalidPrototype(): void
    {
        $this->expectError();
        $pdo = new PDOx('sqlite::memory:', options: [PDOx::PDOX_NAMING_UNDERSCORE_TO_CAMELCASE => false]);
        $pdo->exec('CREATE TABLE test (pkey int primary key)');
        $pdo->exec('INSERT INTO test (pkey) VALUES (1)');

        $data = $pdo->fetchObject('SELECT * FROM test', new TestClassForTestFetchObjectWithoutHydratorWithInvalidPrototype());
        $this->assertNotNull($data);
        $this->assertEquals(1, $data->pkey);
        /** @noinspection PhpUnusedLocalVariableInspection */
        $field = $data->test;
    }

    public function testFetchObjectWithInvalidQuery(): void
    {
        $this->expectException(PDOException::class);
        $pdo = new PDOx('sqlite::memory:');
        $pdo->exec('CREATE TABLE test (pkey int primary key)');
        $pdo->exec('INSERT INTO test (pkey) VALUES (1)');
        $pdo->exec('INSERT INTO test (pkey) VALUES (2)');

        $pdo->fetchObject('SELECT FROM test', new TestClassForTestFetchObjectWithoutHydrator());
    }

    public function testFetchObjectWithHydratorNoStrategies(): void
    {
        $pdo = new PDOx('sqlite::memory:');
        $pdo->exec('CREATE TABLE test (pkey_id int primary key)');
        $pdo->exec('INSERT INTO test (pkey_id) VALUES (1)');

        /** @var TestClassForTestFetchObjectWithHydrator $data */
        $data = $pdo->fetchObject('SELECT * FROM test', new TestClassForTestFetchObjectWithHydrator());

        $this->assertNotNull($data);
        $this->assertEquals(1, $data->pkeyId);
    }

    public function testFetchObjectWithHydratorAndStrategies(): void
    {
        $pdo = new PDOx('sqlite::memory:');
        $pdo->exec('CREATE TABLE test (pkey_id int primary key, active bool)');
        $pdo->exec('INSERT INTO test (pkey_id, active) VALUES (1, true)');

        /** @var TestClassForTestFetchObjectWithHydratorAndStrategies $data */
        $data = $pdo->fetchObject('SELECT * FROM test', new TestClassForTestFetchObjectWithHydratorAndStrategies(), strategies: ['active' => new BooleanStrategy('1', '0')]);

        $this->assertNotNull($data);
        $this->assertEquals(1, $data->pkeyId);
        $this->assertTrue($data->active);
    }

    public function testFetchObjectWithHydratorMultipleEntries(): void
    {
        $this->expectException(InvalidQueryException::class);
        $this->expectExceptionMessage('Query returned more than one result');
        $pdo = new PDOx('sqlite::memory:');
        $pdo->exec('CREATE TABLE test (pkey_id int primary key)');
        $pdo->exec('INSERT INTO test (pkey_id) VALUES (1)');
        $pdo->exec('INSERT INTO test (pkey_id) VALUES (2)');

        $pdo->fetchObject('SELECT * FROM test', new TestClassForTestFetchObjectWithoutHydrator());
    }

    public function testFetchObjectWithHydratorWithInvalidPrototype(): void
    {
        $this->expectError();
        $pdo = new PDOx('sqlite::memory:', options: [PDOx::PDOX_NAMING_UNDERSCORE_TO_CAMELCASE => false]);
        $pdo->exec('CREATE TABLE test (pkey_id int primary key)');
        $pdo->exec('INSERT INTO test (pkey_id) VALUES (1)');

        $data = $pdo->fetchObject('SELECT * FROM test', new TestClassForTestFetchObjectWithHydratorWithInvalidPrototype());
        $this->assertNotNull($data);
        $this->assertEquals(1, $data->pkey);
        /** @noinspection PhpUnusedLocalVariableInspection */
        $field = $data->testField;
    }

    public function testFetchObjectWithHydratorNoResultNull(): void
    {
        $pdo = new PDOx('sqlite::memory:');
        $pdo->exec('CREATE TABLE test (pkey_id int primary key)');
        $pdo->exec('INSERT INTO test (pkey_id) VALUES (1)');
        $pdo->exec('INSERT INTO test (pkey_id) VALUES (2)');

        $result = $pdo->fetchObject('SELECT * FROM test WHERE pkey_id > 4', new TestClassForTestFetchObjectWithHydrator());
        $this->assertNull($result);
    }

    public function testFetchObjectWithHydratorNoResultException(): void
    {
        $this->expectException(NoResultException::class);
        $this->expectExceptionMessage('Query returned no result');
        $pdo = new PDOx('sqlite::memory:', options: [PDOx::PDOX_NO_RESULT_BEHAVIOR => PDOx::PDOX_NO_RESULT_BEHAVIOR_EXCEPTION]);
        $pdo->exec('CREATE TABLE test (pkey_id int primary key)');
        $pdo->exec('INSERT INTO test (pkey_id) VALUES (1)');
        $pdo->exec('INSERT INTO test (pkey_id) VALUES (2)');

        $pdo->fetchObject('SELECT * FROM test WHERE pkey_id > 4', new TestClassForTestFetchObjectWithHydrator());
    }
}
