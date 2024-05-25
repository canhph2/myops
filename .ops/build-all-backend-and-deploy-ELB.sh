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
trap 'myops post-work --type=finish --process-id=${PROCESS_ID} --exit-code=$? ' EXIT
#
eval "$(myops pre-work --response-type=eval)"
myops pre-work --type=start
# handle
php app/MyOps.php sync
php app/MyOps.php slack --message="${DEVICE} just synced $(myops version no-format-color) successfully" \
                        --process-id=${PROCESS_ID} --exit-code=$?

