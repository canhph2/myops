# ops-lib
*DevOps | CICD | DevOps Shared Library*

---
### Usage
- *Add this and the other project's README.MD (should be in the DevOps Notes section):*

---
### ops-lib | to sync new code (manually)
```shell
sh _ops/lib_temp/sync.sh
```

---
## Project Notes

### Note 1 | How to work
1. Code ops shared library in PHP
2. Combine / build all files into 1 file php which call ``lib``
   - Combine all file into ``lib``
   - Test all function every release new code
   - Handle with version
3. Deploy to other project, should be ``_ops/lib``

### Note 2 | .syncignore | sync ignore list
Add a list of unused files and directories in other projects here

### Note 3 | Project structure
- In others projects:
```
_ops/lib    (PHP)
```
- In this project (development and library):
- [RELEASE] Building project will result ``_ops/lib``
  - add new files to FILES_LIST in Release class 
  - combine and release
```shell
php App/app.php release
echo " todo php test here"
```
- release and run: 
```
php _ops/lib
```
- test local project
```shell
php App/app.php help
```
