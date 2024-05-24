#!/bin/bash
set -e # tells the shell to exit if a command returns a non-zero exit status
# [Alias required] load shell configuration
[[ -f ~/.zshrc ]] && source ~/.zshrc # MAC
[[ -f ~/.bashrc ]] && source ~/.bashrc # Ubuntu

# usage:    sh .ops/sync-and-notify-Slack.sh 'DEVICE_NAME'

#
eval "$(php app/MyOps.php pre-work --response-type=eval)"
php app/MyOps.php pre-work
# validate
php app/MyOps.php validate --type=device --type=branch
# handle
php app/MyOps.php sync
php app/MyOps.php slack --message="${DEVICE} just synced $(myops version no-format-color) successfully" \
                        --process-id=${PROCESS_ID} --exit-code=$?

