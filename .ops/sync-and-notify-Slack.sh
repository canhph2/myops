#!/bin/bash
set -e # tells the shell to exit if a command returns a non-zero exit status
# [Alias required] load shell configuration
[[ -f ~/.zshrc ]] && source ~/.zshrc # MAC
[[ -f ~/.bashrc ]] && source ~/.bashrc # Ubuntu

# usage:    sh .ops/sync-and-notify-Slack.sh 'DEVICE_NAME'

# cleanup: in case success, in case failure and exit with code at any commands
trap 'php app/MyOps.php post-work --process-id=${PROCESS_ID} --exit-code=$?    --message="${DEVICE} just synced $(myops version no-format-color)" ' EXIT
#
eval "$(php app/MyOps.php pre-work --response-type=eval)"
php app/MyOps.php pre-work
# validate
php app/MyOps.php validate --type=device --type=branch
# handle
php app/MyOps.php sync
