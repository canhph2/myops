<?php

namespace App\Enum;

class CommandEnum
{
    // === this app commands ===
    const HELP = 'help';
    const RELEASE = 'release';
    // === ops commands ===
    const BRANCH = 'branch';
    const REPOSITORY = 'repository';
    const HEAD_COMMIT_ID = 'head-commit-id';
    const HOME_DIR = 'home-dir';
    const SCRIPT_DIR = 'script-dir';
    const WORKING_DIR = 'working-dir';

    /**
     * @var array
     * key => value | key is command, value is description
     */
    const SUPPORT_COMMANDS = [
        self::HELP => 'show list support command and usage',
        self::RELEASE => 'combine all PHP files into \'_ops/lib\'',
        //
        self::BRANCH => 'get git branch / GitHub branch',
        self::REPOSITORY => 'get GitHub repository name',
        self::HEAD_COMMIT_ID => 'get head commit id of branch',
        self::HOME_DIR => 'return home directory of machine / server',
        self::SCRIPT_DIR => 'return directory of script',
        self::WORKING_DIR => 'get root project directory / current working directory',
    ];
}
