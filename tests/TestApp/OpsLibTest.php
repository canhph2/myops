<?php

namespace TestApp;

require_once 'app/Objects/Version.php';
require_once 'app/Objects/Process.php';
require_once 'app/Helpers/DirHelper.php';
require_once 'tests/TestApp/BaseTestCase.php';

use app\Enum\TagEnum;
use app\Helpers\DIR;
use app\Objects\Process;
use app\Objects\Version;


class OpsLibTest extends BaseTestCase
{
    public function testAllFunctions()
    {
        $this->assertIsString("OpsLibTest test string");
    }

    public function testVersionObject()
    {
        $version = Version::parse("1.0.0.0");
        $this->customAssertIsStringAndContainsString("1.0.0.0", $version->toStringFull());
        $version->bump(); // default
        $this->customAssertIsStringAndContainsString("1.0.1.0", $version->toStringFull());
        $version->bump(Version::BUILD);
        $this->customAssertIsStringAndContainsString("1.0.1.1", $version->toStringFull());
        $version->bump(Version::PATCH);
        $this->customAssertIsStringAndContainsString("1.0.2.0", $version->toStringFull());
        $version->bump(Version::MINOR);
        $this->customAssertIsStringAndContainsString("1.1.0.0", $version->toStringFull());
        $version->bump(Version::MAJOR);
        $this->customAssertIsStringAndContainsString("2.0.0.0", $version->toStringFull());
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
        $envContent = (new Process(__FUNCTION__, DIR::getWorkingDir(), [
            "php _ops/lib load-env-ops"
        ]))->execMulti()->getOutputStrAll();
        $this->customAssertIsStringAndContainsString("SLACK_BOT_TOKEN", $envContent);
        $this->customAssertIsStringAndContainsString("GITHUB_PERSONAL_ACCESS_TOKEN", $envContent);
        $this->customAssertIsStringAndContainsString("ENGAGEPLUS_CACHES_DIR", $envContent);
    }

    public function testReplaceTextInFile()
    {
        exec('php _ops/lib tmp add');
        // create a test file
        $contentOrigin = "line 1 with AA BB CC";
        file_put_contents("tmp/test.txt", $contentOrigin);
        exec("php _ops/lib replace-text-in-file 'BB' 'NEW TEST OK' 'tmp/test.txt'");
        $contentNew = exec("cat tmp/test.txt");
        self::assertTrue($contentOrigin !== $contentNew);
    }

    public function testELBUpdateVersion()
    {
        $this->customAssertIsStringAndContainsString("[ENV] missing ", exec("php _ops/lib elb-update-version"));
    }

    public function testAWSGetENV(){
        $this->customAssertIsStringAndContainsString(TagEnum::SUCCESS, exec("php _ops/lib get-secret-env WRONG-ENVFILE"));
        $this->customAssertIsStringAndContainsString(TagEnum::ERROR, exec("php _ops/lib get-secret-env env-email-dev"));
    }

}
