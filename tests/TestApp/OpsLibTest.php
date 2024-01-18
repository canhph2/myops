<?php

namespace TestApp;

require_once 'app/Objects/Version.php';
require_once 'app/Objects/Process.php';
require_once 'app/Objects/TextLine.php';
require_once 'app/Enum/IndentLevelEnum.php';
require_once 'app/Enum/TagEnum.php';
require_once 'app/Enum/UIEnum.php';
require_once 'app/Enum/IconEnum.php';
require_once 'app/Helpers/TEXT.php';
require_once 'app/Helpers/DIR.php';
require_once 'app/Helpers/STR.php';
require_once 'app/Helpers/UI.php';
//
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
        $result1 = (new Process(__FUNCTION__, DIR::getWorkingDir(), [
            "php _ops/lib elb-update-version"
        ]))->setIsExistOnError(false)->execMulti()->getOutputStrAll();
        $this->customAssertIsStringAndContainsString("missing BRANCH or REPOSITORY or ENV", $result1);
    }

    public function testAWSGetENV()
    {
        $result1 = (new Process(__FUNCTION__, DIR::getWorkingDir(), [
            "php _ops/lib get-secret-env env-email-dev"
        ]))->setIsExistOnError(false)->execMulti()->getOutputStrAll();
        $this->customAssertIsStringAndContainsString(TagEnum::SUCCESS, $result1);
        //
        $result2 = (new Process(__FUNCTION__, DIR::getWorkingDir(), [
            "php _ops/lib get-secret-env WRONG-ENVFILE"
        ]))->setIsExistOnError(false)->execMulti()->getOutputStrAll();
        $this->customAssertIsStringAndContainsString(TagEnum::ERROR, $result2);
    }

    public function testUITitleSubTitleFuncs(){
        // validate title
        $result1 = (new Process(__FUNCTION__, DIR::getWorkingDir(), [
            "php _ops/lib title"
        ]))->setIsExistOnError(false)->execMulti()->getOutputStrAll();
        $this->customAssertIsStringAndContainsString(TagEnum::ERROR, $result1);
        // title
        $result1 = (new Process(__FUNCTION__, DIR::getWorkingDir(), [
            "php _ops/lib title 'this is test title'"
        ]))->setIsExistOnError(false)->execMulti()->getOutputStrAll();
        $this->customAssertIsStringAndContainsString("===", $result1);
        $this->customAssertIsStringAndContainsString("test title", $result1);
        // validate sub title
        $result1 = (new Process(__FUNCTION__, DIR::getWorkingDir(), [
            "php _ops/lib sub-title"
        ]))->setIsExistOnError(false)->execMulti()->getOutputStrAll();
        $this->customAssertIsStringAndContainsString(TagEnum::ERROR, $result1);
        // sub title
        $result1 = (new Process(__FUNCTION__, DIR::getWorkingDir(), [
            "php _ops/lib sub-title 'this is test sub title'"
        ]))->setIsExistOnError(false)->execMulti()->getOutputStrAll();
        $this->customAssertIsStringAndContainsString("--", $result1);
        $this->customAssertIsStringAndContainsString("test sub title", $result1);
        // validate
    }

}
