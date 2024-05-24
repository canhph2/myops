# MyOps v3.9.8
*DevOps | CICD | MyOps App (PHP)*

## === Usage ===
- *Add this instruction in the project's README.MD (should be in the DevOps Notes section):*

---
## === DevOps Notes ===
- Require a tech stacks: `Mac 14`/`Ubuntu 18`, `Docker 20.x`, `nvm` and `node 12` (Angular), `php 7.2`, `aws 2.x`, `gh 2.47` (GitHub CLI) | all (or above).
- Require a `myops` app, a support application for DevOps, can clone at https://github.com/congnqnexlesoft/myops (private)
### Manual Build And Deploy
- Checkout the branch: `develop`, `staging`, `master`
- Run the command below:
```shell
sh .ops/build-and-deploy-with-caches.sh 'Developer (manual)'
```

---
## === Project Notes ===

### Note 0 | Install
- **First time**, copy full `.release/MyOps.php` to machine, and run installing `myops sync`
- Next time, just sync new version `myops sync`
- [Alias required] add these commands below in a beginning of your shell script file:
```shell
# [Alias required] load shell configuration
[[ -f ~/.zshrc ]] && source ~/.zshrc # MAC
[[ -f ~/.bashrc ]] && source ~/.bashrc # Ubuntu
```

### Note 1 | How to work
1. Combine / build all files into 1 file php at `.release/MyOps.php` (`PHP`)
   - Test all functions when release a new code
   - Handling with version
2. Install in new machine or sync an exist app, this command should run fine `myops sync`

### Note 2 | Usage
- use alias `myops` anywhere in Mac / Linux / Docker image (OS)
- shell configuration files of Mac at `~/.zshrc`, of Ubuntu at `~/.bashrc`
```shell
alias myops="php <HOME DIR>/<ENGAGE CACHES DIR>/myops/.release/MyOps.php"
```
- Usage: `myops COMMAND PARAM1`
- In this project (development and library):
- [RELEASE] Building project will combine at `.release/MyOps.php`
  - Add new files to FILES_LIST in Release class
  - Combine and release
```shell
php app/MyOps.php release
```
```shell
php app/MyOps.php sync
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
