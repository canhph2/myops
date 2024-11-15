<?php

namespace App\Enum;

class ValidationTypeEnum
{
    const BRANCH = 'branch';
    const DOCKER = 'docker';
    const DEVICE = 'device';
    const FILE_CONTAINS_TEXT = 'file-contains-text';
    const EXISTS = 'exists';
    const DONT_EXISTS = 'dont-exists';

    // ===
    const SUPPORT_LIST = [self::BRANCH, self::DOCKER, self::DEVICE, self::FILE_CONTAINS_TEXT, self::EXISTS];
}
