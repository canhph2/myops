# ops-app v3.2.2
*DevOps | CICD | Ops App (PHP)*

## === Usage ===
- *Add this and the other project's README.MD (should be in the DevOps Notes section):*


---
## === DevOps Notes ===
- Tech stacks: `Mac 14`/`Ubuntu 18`, `Docker 20.x`, `nvm` and `node 12` (Angular), `php 7.2`, `aws 2.x`, `gh 2.47` (GitHub CLI) | all (or above).
- Required: copy `ops-app` to **your home dir** `~`, expected `~/ops-app` can run.
### Manual Build And Deploy
- Manual Build And Deploy
    - Checkout branch: `develop`, `staging`, `master`
    - Run command:
```shell
sh _ops/build-and-deploy-with-caches.sh 'Developer (manual)'
```
---
### ops-app | to sync new code (manually)
```shell
php ~/ops-app sync
```


---
## === Project Notes ===

### Note 1 | How to work
1. Code ops shared library in PHP
2. Combine / build all files into 1 file php which call `.release/ops-app` (`PHP`)
   - Combine all file into `ops-app` (`PHP`)
   - Test all function every release new code
   - Handle with version
3. Deploy to machine's home working directory, should be `~/ops-app`

### Note 2 | Project structure
- In others projects, Mac, Linux, Docker images: `~/ops-app`
  - Usage: `php ~/ops-app COMMAND PARAM1`
---
- In this project (development and library):
- [RELEASE] Building project will result `~/ops-app`
  - add new files to FILES_LIST in Release class
  - combine and release
```shell
php app/ops-app release
php vendor/phpunit/phpunit/phpunit tests
```

### Note 3 | AWS services
#### ELB - Elastic Beanstalk
- ELB application version structure
```
<EB_APP_VERSION directory .zip>    (zip file)
    .ebextensions    (dir)
        blockdevice-xvdcz.config    (file)
    Dockerrun.aws.json    (file)
```
- EngagePlus ELB notes:
  - 2nd disk size - blockdevice-xvdcz.config: `master = 100 (GB)`, `staging = 20 (GB)`, `develop = 20 (GB)`
  - Mail catcher port - Dockerrun.aws.json: `staging, develop =  "hostPort": 1025,  "containerPort": 1025`

### Note 4 | Not use common name like `.env`, should use another name => change `.env` to another name:
- change `.env` file in backend projects to `.conf-ryt`

---
## === Test  | PHPUNIT ===
- add phpunit (v7.0 for php 7.2): `composer require phpunit/phpunit:^7.0 --dev`
- run test
```shell
  php vendor/phpunit/phpunit/phpunit tests
```

---
## === Versions ===
Readme in **VERSIONS.MD**
