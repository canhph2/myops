#!/bin/bash
set -e # tells the shell to exit if a command returns a non-zero exit status
# [Alias required] load shell configuration
[[ -f ~/.zshrc ]] && source ~/.zshrc # MAC
[[ -f ~/.bashrc ]] && source ~/.bashrc # Ubuntu

# usage:    sh .ops/sync-and-notify-Slack.sh 'DEVICE_NAME'

#
eval "$(myops pre-work --response-type=eval)"
myops pre-work
# validate
myops validate --type=device --type=branch
# handle
myops sync
myops slack --message="${DEVICE} just synced $(myops version no-format-color) successfully" --process-id=${PROCESS_ID}
