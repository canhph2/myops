<?php

namespace TestApp;

require_once 'app/Traits/ConsoleBaseTrait.php';
require_once 'app/Traits/ConsoleUITrait.php';
require_once 'app/Classes/Version.php';
require_once 'app/Classes/Process.php';
require_once 'app/Classes/TextLine.php';
require_once 'app/Enum/AppInfoEnum.php';
require_once 'app/Enum/IndentLevelEnum.php';
require_once 'app/Enum/TagEnum.php';
require_once 'app/Enum/UIEnum.php';
require_once 'app/Enum/IconEnum.php';
require_once 'app/Enum/GitHubEnum.php';
require_once 'app/Helpers/DirHelper.php';
require_once 'app/Helpers/StrHelper.php';
require_once 'app/Helpers/UuidHelper.php';
//
require_once 'tests/TestApp/BaseTestCase.php';

use App\Classes\Process;
use App\Classes\Version;
use App\Enum\TagEnum;
use App\Helpers\DirHelper;
use App\Helpers\UuidHelper;


class MyOpsTest extends BaseTestCase
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
            "myops version"
        ]))->execMulti()->getOutputStrAll();
        $this->customAssertIsStringAndContainsString("MyOps v", $oldVersion);
        $this->customAssertIsStringAndContainsString("v", $oldVersion);
        $this->customAssertIsStringAndContainsString(".", $oldVersion);
    }

    public function testLoadOpsEnv()
    {
        $envContent = (new Process(__FUNCTION__, DirHelper::getWorkingDir(), [
            "myops load-env-ops"
        ]))->execMulti()->getOutputStrAll();
        $this->customAssertIsStringAndContainsString("SLACK_BOT_TOKEN", $envContent);
        $this->customAssertIsStringAndContainsString("GITHUB_PERSONAL_ACCESS_TOKEN", $envContent);
        $this->customAssertIsStringAndContainsString("ENGAGEPLUS_CACHES_DIR", $envContent);
    }

    public function testAddTmpDir()
    {
        // add a 'tmp' dir
        (new Process(__FUNCTION__, DirHelper::getWorkingDir(), [
            "myops tmp add"
        ]))->execMultiInWorkDir();
        // test the result
        $this->customAssertIsStringAndContainsString(join(' | ', TagEnum::VALIDATION_SUCCESS),
            (new Process(__FUNCTION__, DirHelper::getWorkingDir(), [
                sprintf("myops validate exists '%s' tmp", DirHelper::getWorkingDir())
            ]))->execMultiInWorkDirAndGetOutputStrAll()
        );
        // test the result
        $this->customAssertIsStringAndContainsString(join(' | ', TagEnum::VALIDATION_SUCCESS),
            (new Process(__FUNCTION__, DirHelper::getWorkingDir(), [
                sprintf("myops validate dont-exists '%s' tmp", DirHelper::getWorkingDir())
            ]))->execMultiInWorkDirAndGetOutputStrAll()
        );
    }

    /*
     * require add tmp dir (above)
     */
    public function testReplaceTextInFile()
    {
        // create a test file
        $contentOrigin = "line 1 with AA BB CC";
        file_put_contents("tmp/test.txt", $contentOrigin);
        // handle replace
        (new Process(__FUNCTION__, DirHelper::getWorkingDir(), [
            "myops replace-text-in-file 'BB' 'NEW TEST OK' 'tmp/test.txt'"
        ]))->execMulti();
        // test the result
        $this->customAssertIsStringAndContainsString(join(' | ', TagEnum::VALIDATION_SUCCESS),
            (new Process(__FUNCTION__, DirHelper::getWorkingDir(), [
                "myops validate file-contains-text tmp/test.txt NEW TEST OK"
            ]))->execMultiInWorkDirAndGetOutputStrAll()
        );
    }

    public function testELBUpdateVersion()
    {
        $result1 = (new Process(__FUNCTION__, DirHelper::getWorkingDir(), [
            "myops elb-update-version"
        ]))->setIsExitOnError(false)->execMulti()->getOutputStrAll();
        $this->customAssertIsStringAndContainsString("missing BRANCH or REPOSITORY or ENV", $result1);
    }

    public function testAWSGetENV()
    {
        $result1 = (new Process(__FUNCTION__, DirHelper::getWorkingDir(), [
            "myops get-secret-env env-email-dev"
        ]))->setIsExitOnError(false)->execMulti()->getOutputStrAll();
        $this->customAssertIsStringAndContainsString(TagEnum::SUCCESS, $result1);
        //
        $result2 = (new Process(__FUNCTION__, DirHelper::getWorkingDir(), [
            "myops get-secret-env WRONG-ENVFILE"
        ]))->setIsExitOnError(false)->execMulti()->getOutputStrAll();
        $this->customAssertIsStringAndContainsString(TagEnum::ERROR, $result2);
    }

    public function testUITitleSubTitleFuncs()
    {
        // validate title
        $result1 = (new Process(__FUNCTION__, DirHelper::getWorkingDir(), [
            "myops title"
        ]))->setIsExitOnError(false)->execMulti()->getOutputStrAll();
        $this->customAssertIsStringAndContainsString(TagEnum::ERROR, $result1);
        // title
        $result1 = (new Process(__FUNCTION__, DirHelper::getWorkingDir(), [
            "myops title 'this is test title'"
        ]))->setIsExitOnError(false)->execMulti()->getOutputStrAll();
        $this->customAssertIsStringAndContainsString("===", $result1);
        $this->customAssertIsStringAndContainsString("test title", $result1);
        // validate sub title
        $result1 = (new Process(__FUNCTION__, DirHelper::getWorkingDir(), [
            "myops sub-title"
        ]))->setIsExitOnError(false)->execMulti()->getOutputStrAll();
        $this->customAssertIsStringAndContainsString(TagEnum::ERROR, $result1);
        // sub title
        $result1 = (new Process(__FUNCTION__, DirHelper::getWorkingDir(), [
            "myops sub-title 'this is test sub title'"
        ]))->setIsExitOnError(false)->execMulti()->getOutputStrAll();
        $this->customAssertIsStringAndContainsString("--", $result1);
        $this->customAssertIsStringAndContainsString("test sub title", $result1);
        // validate
    }

    public function testUUIdHelper()
    {
        $this->assertIsString(UuidHelper::generateUuid4Native());
        $this->assertFalse(UuidHelper::isValid("abcd"));
        $this->assertTrue(UuidHelper::isValid(UuidHelper::generateUuid4Native()));
    }

    public function testProcess()
    {
        $processId = (new Process(__FUNCTION__, DirHelper::getWorkingDir(), [
            "myops process start"
        ]))->execMultiInWorkDirAndGetOutputStrAll();
        $this->assertIsString($processId);
        $this->assertTrue(UuidHelper::isValid($processId));
    }

}
