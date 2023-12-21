<?php

namespace tests;

class OpsLibTest extends BaseTestCase
{
    public function testAllFunctions()
    {
        $this->assertIsString("OpsLibTest test string");
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
        $this->customAssertIsStringAndContainsString(" ENGAGEPLUS_CACHES_DIR", $data);
    }

}
