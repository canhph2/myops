#!/bin/bash
set -e # tells the shell to exit if a command returns a non-zero exit status
# [Alias required] load shell configuration
[[ -f ~/.zshrc ]] && source ~/.zshrc # MAC
[[ -f ~/.bashrc ]] && source ~/.bashrc # Ubuntu

# usage:    sh .ops/build-all-frontend-and-deploy-to-S3.sh 'DEVICE_NAME'

# cleanup: in case success, in case failure and exit with code at any commands
trap 'myops post-work --process-id=${PROCESS_ID} --exit-code=$?    --message="${DEVICE} just finished deploying all frontend projects to S3 bucket" ' EXIT
#
eval "$(myops pre-work --response-type=eval)"
myops pre-work --message="${DEVICE} starts to build all frontend projects:"
# validate
myops validate --type=device --type=branch
# handle
#    just prepare a caches directory of myops
myops checkout-caches
cd "${ENGAGEPLUS_CACHES_REPOSITORY_DIR}"
#    Build Angular projects and deploy a new version to S3 bucket
#        Admin SPA
export REPOSITORY=engage-spa # to switch repository
myops checkout-caches ${REPOSITORY} ${BRANCH}
cd "${ENGAGEPLUS_CACHES_DIR}/${REPOSITORY}"
chmod u+x ".ops/build-Angular-and-deploy-to-S3.sh" && . ".ops/build-Angular-and-deploy-to-S3.sh"
myops slack --indent=1 --message="just finished building Angular project of Admin SPA (frontend) :heavy_check_mark:"
#        Booking SPA
export REPOSITORY=engage-booking-spa # to switch repository
myops checkout-caches ${REPOSITORY} ${BRANCH}
cd "${ENGAGEPLUS_CACHES_DIR}/${REPOSITORY}"
chmod u+x ".ops/build-Angular-and-deploy-to-S3.sh" && . ".ops/build-Angular-and-deploy-to-S3.sh"
myops slack --indent=1 --message="just finished building Angular project of Booking SPA (frontend) :heavy_check_mark:"
