# MyOps App Document
*MyOps is an application designed for support DevOps operation*

## Using MyOps
MyOps is executed from the command line using the following format:
- version 3.6 format
```shell
php path/MyOps.php command sub-command arg2 arg3 ...
php path/MyOps.php command arg1        arg2 arg3
[alias]      myops command sub-command arg1 arg2 ...
[alias]      myops command arg1        arg2 arg3 ...
```
- version 3.7 format will support field value arguments `--field1=value1`
- ```shell
[alias]      myops command sub-command arg1 arg2 ... --field1=value1 --field2=value2
```
php
