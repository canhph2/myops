<?php

namespace App\Helpers;

use App\Enum\ENVEnum;

class ENVHelper
{
    /**
     * @return string|null
     */
    public static function getENVCode(): ?string
    {
        return strtolower(getenv('SYS_ENV'));
    }

    /**
     * @return string
     */
    public static function getENVText(): string
    {
        switch (self::getENVCode()) {
            case ENVEnum::PRODUCTION_CODE:
            case 'prod':
            case ENVEnum::PRODUCTION:
                return ENVEnum::PRODUCTION;
            case ENVEnum::STAGING_CODE:
                return ENVEnum::STAGING;
            case ENVEnum::DEVELOP_CODE:
                return ENVEnum::DEVELOP;
            case ENVEnum::LOCAL_CODE:
            case ENVEnum::LOCAL:
                return ENVEnum::LOCAL;
            default:
                return 'unknown_env';
        }
    }
}
