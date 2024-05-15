<?php

namespace TestApp;

use PHPUnit\Framework\TestCase;

class BaseTestCase extends TestCase
{
    protected function customAssertIsStringAndContainsString(string $searchStr = null, string $toCheckStr = null)
    {
        $this->assertIsString($toCheckStr);
        $this->assertStringContainsString($searchStr, $toCheckStr);
    }
    public function testAllFunctions()
    {
        $this->assertIsString("example test string");
    }
}
