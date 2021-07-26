<?php

namespace Jinya\Tests;

use Jinya\PDOx\PDOx;
use PHPUnit\Framework\TestCase;

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
}
