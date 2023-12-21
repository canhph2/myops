<?php

namespace tests;

class OpsLibTest extends BaseTestCase
{
    public function testAllFunctions()
    {
        $this->assertIsString("OpsLibTest test string");
    }

    public function testCommandHelp()
    {
        $oldVersion = exec("php App/app.php version");
        $this->customAssertIsStringAndContainsString( "OPS SHARED LIBRARY (PHP)", $oldVersion);
        print $oldVersion;
        //
        exec("php App/app.php release");
        //
        $newVersion = exec("php App/app.php version");
        $this->customAssertIsStringAndContainsString( "OPS SHARED LIBRARY (PHP)", $newVersion);
        print $newVersion;
        // compare change
        $this->assertTrue($oldVersion === $newVersion);

    }

}
