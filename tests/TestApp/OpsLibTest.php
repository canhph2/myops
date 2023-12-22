<?php

namespace TestApp;

require_once 'App/Objects/Version.php';

use App\Objects\Version;

class OpsLibTest extends BaseTestCase
{
    public function testAllFunctions()
    {
        $this->assertIsString("OpsLibTest test string");
    }

    public function testVersionObject(){
        $version = Version::parse("1.0.0.0");
        $this->customAssertIsStringAndContainsString("1.0.0.0",$version->toStringFull());
        $version->bump(); // default
        $this->customAssertIsStringAndContainsString("1.0.1.0",$version->toStringFull());
        $version->bump(Version::BUILD);
        $this->customAssertIsStringAndContainsString("1.0.1.1",$version->toStringFull());
        $version->bump(Version::PATCH);
        $this->customAssertIsStringAndContainsString("1.0.2.0",$version->toStringFull());
        $version->bump(Version::MINOR);
        $this->customAssertIsStringAndContainsString("1.1.0.0",$version->toStringFull());
        $version->bump(Version::MAJOR);
        $this->customAssertIsStringAndContainsString("2.0.0.0",$version->toStringFull());
    }

    public function testCommandVersion()
    {
        $oldVersion = exec("php _ops/lib version");
        $this->customAssertIsStringAndContainsString("OPS SHARED LIBRARY (PHP)", $oldVersion);
        $this->customAssertIsStringAndContainsString("v", $oldVersion);
        $this->customAssertIsStringAndContainsString(".", $oldVersion);
    }

    public function testLoadOpsEnv()
    {
        $output = "";
        exec("php _ops/lib load-env-ops", $output);
        $data = join("\n", $output);
        $this->customAssertIsStringAndContainsString("SLACK_BOT_TOKEN", $data);
        $this->customAssertIsStringAndContainsString("GITHUB_PERSONAL_ACCESS_TOKEN", $data);
        $this->customAssertIsStringAndContainsString("ENGAGEPLUS_CACHES_DIR", $data);
    }

    public function testReplaceTextInFile(){
        // todo write test case here
        $this->assertIsString(" ok ok");
    }

}
