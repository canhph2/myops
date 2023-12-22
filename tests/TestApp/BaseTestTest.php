<?php

namespace TestApp;

use PHPUnit\Framework\TestCase;

class BaseTestCase extends TestCase
{
    protected function customAssertIsStringAndContainsString(string $searchStr = null, string $checkingStr = null)
    {
        $this->assertIsString($checkingStr);
        $this->assertStringContainsString($searchStr, $checkingStr);
    }
    public function testAllFunctions()
    {
        $this->assertIsString("example test string");
    }
}
