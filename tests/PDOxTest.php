<?php

namespace Jinya\Tests;

use Error;
use Iterator;
use Jinya\PDOx\Exceptions\InvalidQueryException;
use Jinya\PDOx\Exceptions\NoResultException;
use Jinya\PDOx\HydratorType;
use Jinya\PDOx\PDOx;
use Laminas\Hydrator\Strategy\BooleanStrategy;
use PDOException;
use PHPUnit\Framework\TestCase;

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

class TestClassForTestFetchIteratorWithoutHydrator
{
    public int $pkey;
}

class TestClassForTestFetchIteratorWithoutHydratorWithInvalidPrototype
{
    public string $test;
}

class TestClassForTestFetchIteratorWithHydrator
{
    public int $pkeyId;
}

class TestClassForTestFetchIteratorWithHydratorAndStrategies
{
    public int $pkeyId;
    public bool $active;
}

class TestClassForTestFetchIteratorWithHydratorWithInvalidPrototype
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
            /** @var array<mixed> $result */
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
            /** @var array<mixed> $result */
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

        /** @var TestClassForTestFetchObjectWithoutHydrator $data */
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
        $pdo = new PDOx('sqlite::memory:', options: [PDOx::PDOX_NAMING_UNDERSCORE_TO_CAMELCASE => false]);
        $pdo->exec('CREATE TABLE test (pkey int primary key)');
        $pdo->exec('INSERT INTO test (pkey) VALUES (1)');

        /** @var TestClassForTestFetchObjectWithoutHydratorWithInvalidPrototype $data */
        $data = $pdo->fetchObject('SELECT * FROM test', new TestClassForTestFetchObjectWithoutHydratorWithInvalidPrototype());
        $this->assertNotNull($data);
        /** @phpstan-ignore-next-line */
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

    public function testFetchObjectWithObjectPropertyHydrator(): void
    {
        $pdo = new PDOx('sqlite::memory:', options: [PDOx::PDOX_HYDRATOR_TYPE => HydratorType::ObjectPropertyHydrator]);
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
        $data = $pdo->fetchObject('SELECT * FROM test', new TestClassForTestFetchObjectWithHydratorAndStrategies(), strategies: ['active' => new BooleanStrategy(1, 0)]);

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
        $pdo = new PDOx('sqlite::memory:', options: [PDOx::PDOX_NAMING_UNDERSCORE_TO_CAMELCASE => false]);
        $pdo->exec('CREATE TABLE test (pkey_id int primary key)');
        $pdo->exec('INSERT INTO test (pkey_id) VALUES (1)');

        /** @var TestClassForTestFetchObjectWithHydratorWithInvalidPrototype $data */
        $data = $pdo->fetchObject('SELECT * FROM test', new TestClassForTestFetchObjectWithHydratorWithInvalidPrototype());
        $this->assertNotNull($data);
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


    public function testFetchIteratorWithoutHydrator(): void
    {
        $pdo = new PDOx('sqlite::memory:', options: [PDOx::PDOX_NAMING_UNDERSCORE_TO_CAMELCASE => false]);
        $pdo->exec('CREATE TABLE test (pkey int primary key)');
        $pdo->exec('INSERT INTO test (pkey) VALUES (1)');

        $data = $pdo->fetchIterator('SELECT * FROM test', new TestClassForTestFetchIteratorWithoutHydrator());

        $this->assertNotNull($data);
        $this->assertCount(1, iterator_to_array($data));
    }

    public function testFetchIteratorWithoutHydratorMultipleEntries(): void
    {
        $pdo = new PDOx('sqlite::memory:', options: [PDOx::PDOX_NAMING_UNDERSCORE_TO_CAMELCASE => false]);
        $pdo->exec('CREATE TABLE test (pkey int primary key)');
        $pdo->exec('INSERT INTO test (pkey) VALUES (1)');
        $pdo->exec('INSERT INTO test (pkey) VALUES (2)');

        $result = $pdo->fetchIterator('SELECT * FROM test', new TestClassForTestFetchIteratorWithoutHydrator());
        $this->assertNotNull($result);
        $this->assertCount(2, iterator_to_array($result));
    }

    public function testFetchIteratorWithoutHydratorNoResultNull(): void
    {
        $pdo = new PDOx('sqlite::memory:', options: [PDOx::PDOX_NAMING_UNDERSCORE_TO_CAMELCASE => false]);
        $pdo->exec('CREATE TABLE test (pkey int primary key)');
        $pdo->exec('INSERT INTO test (pkey) VALUES (1)');
        $pdo->exec('INSERT INTO test (pkey) VALUES (2)');

        $result = $pdo->fetchIterator('SELECT * FROM test WHERE pkey > 4', new TestClassForTestFetchIteratorWithoutHydrator());
        $this->assertNotNull($result);
        $this->assertCount(0, iterator_to_array($result));
    }

    public function testFetchIteratorWithoutHydratorWithInvalidPrototype(): void
    {
        try {
            $pdo = new PDOx('sqlite::memory:', options: [PDOx::PDOX_NAMING_UNDERSCORE_TO_CAMELCASE => false]);
            $pdo->exec('CREATE TABLE test (pkey int primary key)');
            $pdo->exec('INSERT INTO test (pkey) VALUES (1)');

            /** @var Iterator<TestClassForTestFetchIteratorWithoutHydratorWithInvalidPrototype> $data */
            $data = $pdo->fetchIterator('SELECT * FROM test', new TestClassForTestFetchIteratorWithoutHydratorWithInvalidPrototype());
            $this->assertNotNull($data);
            foreach ($data as $item) {
                /** @noinspection PhpUnusedLocalVariableInspection */
                $field = $item->test;
            }
            $this->assertTrue(false);
        } catch (Error) {
            $this->assertTrue(true);
        }
    }

    public function testFetchIteratorWithInvalidQuery(): void
    {
        $this->expectException(PDOException::class);
        $pdo = new PDOx('sqlite::memory:');
        $pdo->exec('CREATE TABLE test (pkey int primary key)');
        $pdo->exec('INSERT INTO test (pkey) VALUES (1)');
        $pdo->exec('INSERT INTO test (pkey) VALUES (2)');

        $data = $pdo->fetchIterator('SELECT FROM test', new TestClassForTestFetchIteratorWithoutHydrator());
        $this->assertNotNull($data);
        /** @noinspection LoopWhichDoesNotLoopInspection */
        /** @noinspection PhpStatementHasEmptyBodyInspection */
        /** @noinspection MissingOrEmptyGroupStatementInspection */
        /** @noinspection PhpUnusedLocalVariableInspection */
        foreach ($data as $datum) {
        }
    }

    public function testFetchIteratorWithHydratorNoStrategies(): void
    {
        $pdo = new PDOx('sqlite::memory:');
        $pdo->exec('CREATE TABLE test (pkey_id int primary key)');
        $pdo->exec('INSERT INTO test (pkey_id) VALUES (1)');

        $result = $pdo->fetchIterator('SELECT * FROM test', new TestClassForTestFetchIteratorWithHydrator());

        $this->assertNotNull($result);
        $this->assertCount(1, iterator_to_array($result));
    }

    public function testFetchIteratorWithHydratorAndStrategies(): void
    {
        $pdo = new PDOx('sqlite::memory:');
        $pdo->exec('CREATE TABLE test (pkey_id int primary key, active bool)');
        $pdo->exec('INSERT INTO test (pkey_id, active) VALUES (1, true)');

        /** @var Iterator<TestClassForTestFetchIteratorWithHydratorAndStrategies> $data */
        $data = $pdo->fetchIterator('SELECT * FROM test', new TestClassForTestFetchIteratorWithHydratorAndStrategies(), strategies: ['active' => new BooleanStrategy(1, 0)]);

        $this->assertNotNull($data);
        foreach ($data as $item) {
            $this->assertTrue($item->active);
        }
    }

    public function testFetchIteratorWithHydratorMultipleEntries(): void
    {
        $pdo = new PDOx('sqlite::memory:');
        $pdo->exec('CREATE TABLE test (pkey_id int primary key)');
        $pdo->exec('INSERT INTO test (pkey_id) VALUES (1)');
        $pdo->exec('INSERT INTO test (pkey_id) VALUES (2)');

        $result = $pdo->fetchIterator('SELECT * FROM test', new TestClassForTestFetchIteratorWithoutHydrator());
        $this->assertNotNull($result);
        $this->assertCount(2, iterator_to_array($result));
    }

    public function testFetchIteratorWithHydratorWithInvalidPrototype(): void
    {
        try {
            $pdo = new PDOx('sqlite::memory:', options: [PDOx::PDOX_NAMING_UNDERSCORE_TO_CAMELCASE => false]);
            $pdo->exec('CREATE TABLE test (pkey_id int primary key)');
            $pdo->exec('INSERT INTO test (pkey_id) VALUES (1)');

            /** @var Iterator<TestClassForTestFetchIteratorWithHydratorWithInvalidPrototype> $data */
            $data = $pdo->fetchIterator('SELECT * FROM test', new TestClassForTestFetchIteratorWithHydratorWithInvalidPrototype());
            $this->assertNotNull($data);
            foreach ($data as $item) {
                /** @noinspection PhpUnusedLocalVariableInspection */
                $field = $item->testField;
            }
            $this->assertTrue(false);
        } catch (Error) {
            $this->assertTrue(true);
        }
    }

    public function testFetchIteratorWithHydratorNoResult(): void
    {
        $pdo = new PDOx('sqlite::memory:');
        $pdo->exec('CREATE TABLE test (pkey_id int primary key)');
        $pdo->exec('INSERT INTO test (pkey_id) VALUES (1)');
        $pdo->exec('INSERT INTO test (pkey_id) VALUES (2)');

        $result = $pdo->fetchIterator('SELECT * FROM test WHERE pkey_id > 4', new TestClassForTestFetchIteratorWithHydrator());
        $this->assertCount(0, iterator_to_array($result));
    }


    public function testFetchArrayWithoutHydrator(): void
    {
        $pdo = new PDOx('sqlite::memory:', options: [PDOx::PDOX_NAMING_UNDERSCORE_TO_CAMELCASE => false]);
        $pdo->exec('CREATE TABLE test (pkey int primary key)');
        $pdo->exec('INSERT INTO test (pkey) VALUES (1)');

        $data = $pdo->fetchArray('SELECT * FROM test', new TestClassForTestFetchIteratorWithoutHydrator());

        $this->assertNotNull($data);
        $this->assertCount(1, $data);
    }

    public function testFetchArrayWithoutHydratorMultipleEntries(): void
    {
        $pdo = new PDOx('sqlite::memory:', options: [PDOx::PDOX_NAMING_UNDERSCORE_TO_CAMELCASE => false]);
        $pdo->exec('CREATE TABLE test (pkey int primary key)');
        $pdo->exec('INSERT INTO test (pkey) VALUES (1)');
        $pdo->exec('INSERT INTO test (pkey) VALUES (2)');

        $result = $pdo->fetchArray('SELECT * FROM test', new TestClassForTestFetchIteratorWithoutHydrator());
        $this->assertNotNull($result);
        $this->assertCount(2, $result);
    }

    public function testFetchArrayWithoutHydratorNoResultNull(): void
    {
        $pdo = new PDOx('sqlite::memory:', options: [PDOx::PDOX_NAMING_UNDERSCORE_TO_CAMELCASE => false]);
        $pdo->exec('CREATE TABLE test (pkey int primary key)');
        $pdo->exec('INSERT INTO test (pkey) VALUES (1)');
        $pdo->exec('INSERT INTO test (pkey) VALUES (2)');

        $result = $pdo->fetchArray('SELECT * FROM test WHERE pkey > 4', new TestClassForTestFetchIteratorWithoutHydrator());
        $this->assertNotNull($result);
        $this->assertCount(0, $result);
    }

    public function testFetchArrayWithoutHydratorWithInvalidPrototype(): void
    {
        try {
            $pdo = new PDOx('sqlite::memory:', options: [PDOx::PDOX_NAMING_UNDERSCORE_TO_CAMELCASE => false]);
            $pdo->exec('CREATE TABLE test (pkey int primary key)');
            $pdo->exec('INSERT INTO test (pkey) VALUES (1)');

            $data = $pdo->fetchArray('SELECT * FROM test', new TestClassForTestFetchIteratorWithoutHydratorWithInvalidPrototype());
            $this->assertNotNull($data);
            foreach ($data as $item) {
                /** @noinspection PhpUnusedLocalVariableInspection */
                /** @phpstan-ignore-next-line */
                $field = $item->test;
            }
            $this->assertTrue(false);
        } catch (Error) {
            $this->assertTrue(true);
        }
    }

    public function testFetchArrayWithInvalidQuery(): void
    {
        $this->expectException(PDOException::class);
        $pdo = new PDOx('sqlite::memory:');
        $pdo->exec('CREATE TABLE test (pkey int primary key)');
        $pdo->exec('INSERT INTO test (pkey) VALUES (1)');
        $pdo->exec('INSERT INTO test (pkey) VALUES (2)');

        $data = $pdo->fetchArray('SELECT FROM test', new TestClassForTestFetchIteratorWithoutHydrator());
        $this->assertNotNull($data);
        /** @noinspection LoopWhichDoesNotLoopInspection */
        /** @noinspection PhpStatementHasEmptyBodyInspection */
        /** @noinspection MissingOrEmptyGroupStatementInspection */
        /** @noinspection PhpUnusedLocalVariableInspection */
        foreach ($data as $datum) {
        }
    }

    public function testFetchArrayWithHydratorNoStrategies(): void
    {
        $pdo = new PDOx('sqlite::memory:');
        $pdo->exec('CREATE TABLE test (pkey_id int primary key)');
        $pdo->exec('INSERT INTO test (pkey_id) VALUES (1)');

        $result = $pdo->fetchArray('SELECT * FROM test', new TestClassForTestFetchIteratorWithHydrator());

        $this->assertNotNull($result);
        $this->assertCount(1, $result);
    }

    public function testFetchArrayWithHydratorAndStrategies(): void
    {
        $pdo = new PDOx('sqlite::memory:');
        $pdo->exec('CREATE TABLE test (pkey_id int primary key, active bool)');
        $pdo->exec('INSERT INTO test (pkey_id, active) VALUES (1, true)');

        $data = $pdo->fetchArray('SELECT * FROM test', new TestClassForTestFetchIteratorWithHydratorAndStrategies(), strategies: ['active' => new BooleanStrategy(1, 0)]);

        $this->assertNotNull($data);
        foreach ($data as $item) {
            /** @phpstan-ignore-next-line */
            $this->assertTrue($item->active);
        }
    }

    public function testFetchArrayWithHydratorMultipleEntries(): void
    {
        $pdo = new PDOx('sqlite::memory:');
        $pdo->exec('CREATE TABLE test (pkey_id int primary key)');
        $pdo->exec('INSERT INTO test (pkey_id) VALUES (1)');
        $pdo->exec('INSERT INTO test (pkey_id) VALUES (2)');

        $result = $pdo->fetchArray('SELECT * FROM test', new TestClassForTestFetchIteratorWithoutHydrator());
        $this->assertNotNull($result);
        $this->assertCount(2, $result);
    }

    public function testFetchArrayWithHydratorWithInvalidPrototype(): void
    {
        try {
            $pdo = new PDOx('sqlite::memory:', options: [PDOx::PDOX_NAMING_UNDERSCORE_TO_CAMELCASE => false]);
            $pdo->exec('CREATE TABLE test (pkey_id int primary key)');
            $pdo->exec('INSERT INTO test (pkey_id) VALUES (1)');

            $data = $pdo->fetchArray('SELECT * FROM test', new TestClassForTestFetchIteratorWithHydratorWithInvalidPrototype());
            $this->assertNotNull($data);
            foreach ($data as $item) {
                /** @noinspection PhpUnusedLocalVariableInspection */
                /** @phpstan-ignore-next-line */
                $field = $item->testField;
            }
            $this->assertTrue(false);
        } catch (Error) {
            $this->assertTrue(true);
        }
    }

    public function testFetchArrayWithHydratorNoResult(): void
    {
        $pdo = new PDOx('sqlite::memory:');
        $pdo->exec('CREATE TABLE test (pkey_id int primary key)');
        $pdo->exec('INSERT INTO test (pkey_id) VALUES (1)');
        $pdo->exec('INSERT INTO test (pkey_id) VALUES (2)');

        $result = $pdo->fetchArray('SELECT * FROM test WHERE pkey_id > 4', new TestClassForTestFetchIteratorWithHydrator());
        $this->assertCount(0, $result);
    }
}
