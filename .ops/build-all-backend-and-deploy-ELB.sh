#!/bin/bash
set -e # tells the shell to exit if a command returns a non-zero exit status
# [Alias required] load shell configuration
[[ -f ~/.zshrc ]] && source ~/.zshrc # MAC
[[ -f ~/.bashrc ]] && source ~/.bashrc # Ubuntu

# usage:    sh .ops/build-all-backend-and-deploy-ELB.sh

echo "todo build all backend"
myops branch
exit 0;

# cleanup: in case success, in case failure and exit with code at any commands
POST_WORK_COMMAND="myops post-work --type=finish --process-id=${PROCESS_ID} --exit-code=$?"
trap '${POST_WORK_COMMAND}' EXIT
#
eval "$(myops pre-work --response-type=eval)"
myops pre-work --type=start
# handle


