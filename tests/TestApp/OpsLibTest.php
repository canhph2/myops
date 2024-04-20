<?php

namespace TestApp;

require_once 'app/Traits/ConsoleUITrait.php';
require_once 'app/Classes/Version.php';
require_once 'app/Classes/Process.php';
require_once 'app/Classes/TextLine.php';
require_once 'app/Enum/AppInfoEnum.php';
require_once 'app/Enum/IndentLevelEnum.php';
require_once 'app/Enum/TagEnum.php';
require_once 'app/Enum/UIEnum.php';
require_once 'app/Enum/IconEnum.php';
require_once 'app/Helpers/DirHelper.php';
require_once 'app/Helpers/StrHelper.php';
//
require_once 'tests/TestApp/BaseTestCase.php';

use App\Enum\TagEnum;
use App\Helpers\DirHelper;
use App\Classes\Process;
use App\Classes\Version;
use App\OpsApp;


class OpsLibTest extends BaseTestCase
{
    public function testAllFunctions()
    {
        $this->assertIsString("test string 1");
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
        $oldVersion = (new Process(__FUNCTION__, DirHelper::getWorkingDir(), [
            "ops-app version"
        ]))->execMulti()->getOutputStrAll();
        $this->customAssertIsStringAndContainsString("OPS APP (PHP)", $oldVersion);
        $this->customAssertIsStringAndContainsString("v", $oldVersion);
        $this->customAssertIsStringAndContainsString(".", $oldVersion);
    }

    public function testLoadOpsEnv()
    {
        $envContent = (new Process(__FUNCTION__, DirHelper::getWorkingDir(), [
            "ops-app load-env-ops"
        ]))->execMulti()->getOutputStrAll();
        $this->customAssertIsStringAndContainsString("SLACK_BOT_TOKEN", $envContent);
        $this->customAssertIsStringAndContainsString("GITHUB_PERSONAL_ACCESS_TOKEN", $envContent);
        $this->customAssertIsStringAndContainsString("ENGAGEPLUS_CACHES_DIR", $envContent);
    }

    public function testReplaceTextInFile()
    {
        (new Process(__FUNCTION__, DirHelper::getWorkingDir(), [
            "ops-app tmp add"
        ]))->execMulti();
        // create a test file
        $contentOrigin = "line 1 with AA BB CC";
        file_put_contents("tmp/test.txt", $contentOrigin);
        (new Process(__FUNCTION__, DirHelper::getWorkingDir(), [
            "ops-app replace-text-in-file 'BB' 'NEW TEST OK' 'tmp/test.txt'"
        ]))->execMulti();
        $contentNew = (new Process(__FUNCTION__, DirHelper::getWorkingDir(), [
            "cat tmp/test.txt"
        ]))->execMulti()->getOutputStrAll();
        self::assertTrue($contentOrigin !== $contentNew);
    }

    public function testELBUpdateVersion()
    {
        $result1 = (new Process(__FUNCTION__, DirHelper::getWorkingDir(), [
            "ops-app elb-update-version"
        ]))->setIsExitOnError(false)->execMulti()->getOutputStrAll();
        $this->customAssertIsStringAndContainsString("missing BRANCH or REPOSITORY or ENV", $result1);
    }

    public function testAWSGetENV()
    {
        $result1 = (new Process(__FUNCTION__, DirHelper::getWorkingDir(), [
            "ops-app get-secret-env env-email-dev"
        ]))->setIsExitOnError(false)->execMulti()->getOutputStrAll();
        $this->customAssertIsStringAndContainsString(TagEnum::SUCCESS, $result1);
        //
        $result2 = (new Process(__FUNCTION__, DirHelper::getWorkingDir(), [
            "ops-app get-secret-env WRONG-ENVFILE"
        ]))->setIsExitOnError(false)->execMulti()->getOutputStrAll();
        $this->customAssertIsStringAndContainsString(TagEnum::ERROR, $result2);
    }

    public function testUITitleSubTitleFuncs()
    {
        // validate title
        $result1 = (new Process(__FUNCTION__, DirHelper::getWorkingDir(), [
            "ops-app title"
        ]))->setIsExitOnError(false)->execMulti()->getOutputStrAll();
        $this->customAssertIsStringAndContainsString(TagEnum::ERROR, $result1);
        // title
        $result1 = (new Process(__FUNCTION__, DirHelper::getWorkingDir(), [
            "ops-app title 'this is test title'"
        ]))->setIsExitOnError(false)->execMulti()->getOutputStrAll();
        $this->customAssertIsStringAndContainsString("===", $result1);
        $this->customAssertIsStringAndContainsString("test title", $result1);
        // validate sub title
        $result1 = (new Process(__FUNCTION__, DirHelper::getWorkingDir(), [
            "ops-app sub-title"
        ]))->setIsExitOnError(false)->execMulti()->getOutputStrAll();
        $this->customAssertIsStringAndContainsString(TagEnum::ERROR, $result1);
        // sub title
        $result1 = (new Process(__FUNCTION__, DirHelper::getWorkingDir(), [
            "ops-app sub-title 'this is test sub title'"
        ]))->setIsExitOnError(false)->execMulti()->getOutputStrAll();
        $this->customAssertIsStringAndContainsString("--", $result1);
        $this->customAssertIsStringAndContainsString("test sub title", $result1);
        // validate
    }

}
