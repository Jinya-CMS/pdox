<?php

namespace Jinya\Tests;

use Jinya\PDOx\PDOx;
use PHPUnit\Framework\TestCase;

class PDOxTest extends TestCase
{

    public function test__construct()
    {
        $pdo = new PDOx('sqlite::memory:');
        $pdo->exec('CREATE TABLE test (pkey int primary key)');
        $pdo->exec('INSERT INTO test (pkey) VALUES (1)');
        $data = $pdo->query('SELECT * FROM test');

        $this->assertNotFalse($data);

        $result = $data->fetch(PDOx::FETCH_ASSOC);
        $this->assertNotFalse($result);

        $this->assertArrayHasKey('pkey', $result);
        $this->assertEquals(1, $result['pkey']);
    }
}
