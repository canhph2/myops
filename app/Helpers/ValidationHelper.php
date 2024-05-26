<?php

namespace App\Helpers;

use App\Classes\ValidationObj;
use App\Enum\AppInfoEnum;
use App\Enum\CommandEnum;
use App\Enum\GitHubEnum;
use App\Enum\TagEnum;
use App\Enum\ValidationTypeEnum;
use App\Traits\ConsoleBaseTrait;
use App\Traits\ConsoleUITrait;

class ValidationHelper
{
    use ConsoleBaseTrait, ConsoleUITrait;

    /**
     * create a new ValidationObj
     * @param string|null $error
     * @param array $data
     * @return ValidationObj
     */
    public static function new(string $error = null, array $data = []): ValidationObj
    {
        return new ValidationObj($error, $data);
    }

    /**
     * create a new ValidationObj and set valid status
     * @return ValidationObj
     */
    public static function valid(): ValidationObj
    {
        return self::new()->clearError();
    }

    /**
     * create a new ValidationObj and set invalid status
     * @param string $errorMessage
     * @return ValidationObj
     */
    public static function invalid(string $errorMessage): ValidationObj
    {
        return self::new()->setError($errorMessage);
    }

    // === console zone ===

    /**
     * @return void
     */
    public static function validateCommand(): void
    {
        if (!self::command()) {
            self::lineTagMultiple(TagEnum::VALIDATION_ERROR)
                ->print("Missing a command, should be '%s COMMAND', use the command '%s help' to see more details.",
                    AppInfoEnum::APP_MAIN_COMMAND, AppInfoEnum::APP_MAIN_COMMAND
                );
            exitApp(ERROR_END);
        }
        if (!array_key_exists(self::command(), CommandEnum::SUPPORT_COMMANDS())) {
            self::lineTagMultiple(TagEnum::VALIDATION_ERROR)->print(
                "Do not support the command '%s', use the command '%s help' to see more details.",
                self::command(), AppInfoEnum::APP_MAIN_COMMAND
            );
            exitApp(ERROR_END);
        }
    }

    /**
     * @param string $subCommandNameOrParam1Name
     * @param array $subCommandSupport
     * @return void
     */
    public static function validateSubCommandOrParam1(string $subCommandNameOrParam1Name = 'sub-command or param 1',
                                                      array  $subCommandSupport = []): void
    {
        if (!self::arg(1)) {
            self::lineTagMultiple(TagEnum::VALIDATION_ERROR)->print("missing a %s", $subCommandNameOrParam1Name);
            exitApp(ERROR_END);
        }
        if (!in_array(self::arg(1), $subCommandSupport)) {
            self::lineTagMultiple(TagEnum::VALIDATION_ERROR)->print(
                "Do not support the sub-command '%s', use these sub-command: %s",
                self::arg(1), join(', ', $subCommandSupport)
            );
            exitApp(ERROR_END);
        }
    }

    /**
     * also notify an error message,
     * eg: ['VAR1', 'VAR2']
     * @param array $envVars
     * @return bool
     */
    public static function validateEnvVars(array $envVars): bool
    {
        $envVarsMissing = [];
        foreach ($envVars as $envVar) {
            if (!getenv($envVar)) $envVarsMissing[] = $envVar;
        }
        if (count($envVarsMissing) > 0) {
            self::LineTagMultiple([TagEnum::ERROR, TagEnum::ENV])->print("missing %s", join(" or ", $envVarsMissing));
            return false; // END | case error
        }
        return true; // END | case OK
    }


    /**
     * @return void
     */
    public static function handleValidateInConsole(): void
    {
        self::lineNew()->printTitle("Validate");
        // new
        foreach (self::inputArr('type') as $inputType) {
            self::validateByType($inputType);
        }
    }

    /**
     * @param string|null $type
     * @return void
     */
    private static function validateByType(string $type = null)
    {
        switch ($type) {
            case ValidationTypeEnum::BRANCH:
                self::validateBranch();
                break;
            case ValidationTypeEnum::DOCKER:
                self::validateDocker();
                break;
            case ValidationTypeEnum::DEVICE:
                self::validateDevice();
                break;
            case ValidationTypeEnum::FILE_CONTAINS_TEXT:
                DirHelper::validateFileContainsText();
                break;
            case ValidationTypeEnum::EXISTS:
                DirHelper::validateDirOrFileExisting(ValidationTypeEnum::EXISTS);
                break;
            case ValidationTypeEnum::DONT_EXISTS:
                DirHelper::validateDirOrFileExisting(ValidationTypeEnum::DONT_EXISTS);
                break;
            default:
                self::LineTagMultiple(TagEnum::VALIDATION_ERROR)->print("invalid action, current support:  %s", join(", ", ValidationTypeEnum::SUPPORT_LIST))
                    ->print("should be like eg:   '%s validate branch'", AppInfoEnum::APP_MAIN_COMMAND);
                break;
        }
    }

    /**
     * Parameter priority: custom > env
     * @return void
     */
    public static function validateBranch($customBranch = null)
    {
        $branch = $customBranch ?? getenv('BRANCH');
        if (in_array($branch, GitHubEnum::SUPPORT_BRANCHES)) {
            self::LineTagMultiple(TagEnum::VALIDATION_SUCCESS)->print("validation branch got OK result: %s", $branch);
        } else {
            self::LineTagMultiple(TagEnum::VALIDATION_ERROR)->print("Invalid branch to build | current branch is '%s'", $branch);
            exitApp(ERROR_END);
        }
    }

    /**
     * Docker should is running
     */
    private static function validateDocker()
    {
        $dockerServer = exec("docker version | grep 'Server:'");
        if (trim($dockerServer)) {
            self::LineTagMultiple(TagEnum::VALIDATION_SUCCESS)->print("Docker is running: $dockerServer");
        } else {
            self::LineTagMultiple(TagEnum::VALIDATION_ERROR)->print("Docker isn't running. Please start Docker app.");
            exitApp(ERROR_END);
        }
    }

    /**
     * should have env var: BRANCH
     * @return void
     */
    private static function validateDevice()
    {
        if (getenv('DEVICE')) {
            self::LineTagMultiple(TagEnum::VALIDATION_SUCCESS)->print("validation device got OK result: %s", getenv('DEVICE'));
        } else {
            self::LineTagMultiple(TagEnum::VALIDATION_ERROR)->print("Invalid device | should pass in your command");
            exitApp(ERROR_END);
        }
    }
}
