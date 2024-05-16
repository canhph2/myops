<?php

namespace App\Enum;

use App\Classes\GitHubRepositoryInfo;

class GitHubEnum
{
    // === GitHub commands ===
    const INIT_REPOSITORY_COMMAND = 'git init';
    const RESET_BRANCH_COMMAND = 'git reset --hard HEAD'; // rollback all changing
    const GET_BRANCH_COMMAND = "git symbolic-ref HEAD | sed 's/refs\/heads\///g'";
    const PULL_COMMAND = 'git pull'; // get the newest code
    const ADD_ALL_FILES_COMMAND = 'git add -A';
    const PUSH_COMMAND = 'git push';
    const GET_REMOTE_ORIGIN_URL_COMMAND = 'git config --get remote.origin.url';
    const GET_REPOSITORY_DIR_COMMAND = 'git rev-parse --show-toplevel';
    const GET_HEAD_COMMIT_ID_COMMAND = 'git rev-parse --short HEAD';

    // === Git branches ===
    const MAIN = 'main';
    const MASTER = 'master';
    const STAGING = 'staging';
    const DEVELOP = 'develop';
    const SUPPORT_BRANCHES = [self::MAIN, self::MASTER, self::STAGING, self::DEVELOP];

    // === GitHub users ===
    const INFOHKENGAGE = 'infohkengage';
    const CONGNQNEXLESOFT = 'congnqnexlesoft';

    // === projects / modules / services ===
    //    backend
    const ENGAGE_API = 'engage-api';
    const ENGAGE_BOOKING_API = 'engage-booking-api';
    const INVOICE_SERVICE = 'invoice-service';
    const PAYMENT_SERVICE = 'payment-service';
    const INTEGRATION_API = 'integration-api';
    const EMAIL_SERVICE = 'email-service';
    //    frontend
    const ENGAGE_SPA = 'engage-spa';
    const ENGAGE_BOOKING_SPA = 'engage-booking-spa';
    //    mobile
    const ENGAGE_MOBILE_APP = 'Engage-Mobile-App';
    const ENGAGE_TEACHER_APP = 'engage-teacher-app';
    //    support
    const ENGAGE_API_DEPLOY = 'engage-api-deploy';
    const ENGAGE_DATABASE_UTILS = 'engage-database-utils';
    const MYOPS = 'myops';
    const DOCKER_BASE_IMAGES = 'docker-base-images';
    const ENGAGE_SELENIUM_TEST_1 = 'engage-selenium-test-1';

    /**
     * @return array
     */
    public static function GET_REPOSITORIES_INFO(): array
    {
        return [
            // === projects / modules / services ===
            //    backend
            new GitHubRepositoryInfo(self::ENGAGE_API, 'Admin API (backend)', self::INFOHKENGAGE, true),
            new GitHubRepositoryInfo(self::ENGAGE_BOOKING_API, 'Booking API (backend)', self::INFOHKENGAGE, true),
            new GitHubRepositoryInfo(self::INVOICE_SERVICE, 'Invoice Service (backend)', self::INFOHKENGAGE, true),
            new GitHubRepositoryInfo(self::PAYMENT_SERVICE, 'Payment Service (backend)', self::INFOHKENGAGE, true),
            new GitHubRepositoryInfo(self::INTEGRATION_API, 'Integration API (backend)', self::INFOHKENGAGE, true),
            new GitHubRepositoryInfo(self::EMAIL_SERVICE, 'Email Service (backend)', self::INFOHKENGAGE, true),
            //    frontend
            new GitHubRepositoryInfo(self::ENGAGE_SPA, 'Admin SPA (frontend)', self::INFOHKENGAGE, true),
            new GitHubRepositoryInfo(self::ENGAGE_BOOKING_SPA, 'Booking SPA (frontend)', self::INFOHKENGAGE, true),
            //    mobile
            new GitHubRepositoryInfo(self::ENGAGE_MOBILE_APP, 'EngagePlus App', self::INFOHKENGAGE),
            new GitHubRepositoryInfo(self::ENGAGE_TEACHER_APP, 'EngagePlus Teacher App', self::INFOHKENGAGE),
            //    support
            new GitHubRepositoryInfo(self::ENGAGE_API_DEPLOY, 'API Deploy (CICD)', self::INFOHKENGAGE),
            new GitHubRepositoryInfo(self::ENGAGE_DATABASE_UTILS, 'Engage Database Utilities', self::CONGNQNEXLESOFT),
            new GitHubRepositoryInfo(self::MYOPS, 'MyOps', self::CONGNQNEXLESOFT, true),
            new GitHubRepositoryInfo(self::DOCKER_BASE_IMAGES, '(Engage) Docker Base Images', self::CONGNQNEXLESOFT),
            new GitHubRepositoryInfo(self::ENGAGE_SELENIUM_TEST_1, "(Engage) Selenium Test 1", self::CONGNQNEXLESOFT),
        ];
    }
}
