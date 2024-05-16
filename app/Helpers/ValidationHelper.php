<?php

namespace App\Helpers;

use App\Classes\ValidationObj;
use App\Enum\AppInfoEnum;
use App\Enum\CommandEnum;
use App\Enum\TagEnum;
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
            exit(1); // END app
        }
        if (!array_key_exists(self::command(), CommandEnum::SUPPORT_COMMANDS())) {
            self::lineTagMultiple(TagEnum::VALIDATION_ERROR)->print(
                "Do not support the command '%s', use the command '%s help' to see more details.",
                self::command(), AppInfoEnum::APP_MAIN_COMMAND
            );
            exit(); // END
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
            exit(1); // END app
        }
        if (!in_array(self::arg(1), $subCommandSupport)) {
            self::lineTagMultiple(TagEnum::VALIDATION_ERROR)->print(
                "Do not support the sub-command '%s', use these sub-command: %s",
                self::arg(1), join(', ', $subCommandSupport)
            );
            exit(); // END
        }
    }
}
