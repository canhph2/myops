#!/bin/bash
set -e # tells the shell to exit if a command returns a non-zero exit status
# [Alias required] load shell configuration
[[ -f ~/.zshrc ]] && source ~/.zshrc # MAC
[[ -f ~/.bashrc ]] && source ~/.bashrc # Ubuntu

# usage:    sh .ops/sync-and-notify-Slack.sh 'DEVICE_NAME'

myops version

eval "$(myops load-env-ops)"

myops validate device || exit 1
myops validate branch || exit 1

myops sync
myops slack "[FINISH] ${DEVICE} synced $(myops version no-format-color) successfully"
