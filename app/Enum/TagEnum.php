<?php

namespace App\Enum;

class TagEnum
{
    const NONE = '';
    const VALIDATION = 'VALIDATION';
    const INFO = 'INFO';
    const SUCCESS = 'SUCCESS';
    const ERROR = 'ERROR';
    const PARAMS = 'PARAMS';
    const ENV = 'ENV';
    const FORMAT = 'FORMAT';
    const WORK = 'WORK';
    const GIT = 'GIT/GITHUB';
    const DOCKER = 'DOCKER';
    const SLACK = 'SLACK';

    // progress
    const BEGIN = 'BEGIN';
    const END = 'END';

    const VALIDATION_ERROR = [self::VALIDATION, self::ERROR];
    const VALIDATION_SUCCESS = [self::VALIDATION, self::SUCCESS];
}
