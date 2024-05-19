#!/bin/bash
set -e # tells the shell to exit if a command returns a non-zero exit status
# [Alias required] load shell configuration
[[ -f ~/.zshrc ]] && source ~/.zshrc # MAC
[[ -f ~/.bashrc ]] && source ~/.bashrc # Ubuntu

eval "$(myops load-env-ops)"

php app/MyOps.php validate --type=device --type=branch
echo 1234;
php app/MyOps.php validate device
php app/MyOps.php validate branch
php app/MyOps.php validate docker
