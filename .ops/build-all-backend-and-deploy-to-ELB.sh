#!/bin/bash
set -e # tells the shell to exit if a command returns a non-zero exit status
# [Alias required] load shell configuration
[[ -f ~/.zshrc ]] && source ~/.zshrc # MAC
[[ -f ~/.bashrc ]] && source ~/.bashrc # Ubuntu

# usage:    sh .ops/build-all-backend-and-deploy-to-ELB.sh 'DEVICE_NAME'

# cleanup: in case success, in case failure and exit with code at any commands
#trap 'export REPOSITORY=myops && myops post-work --process-id=${PROCESS_ID} --exit-code=$?    --message="${DEVICE} just finished deploying all backend projects" ' EXIT
#todo
trap 'echo "exit code" && echo $? && myops post-work --process-id=${PROCESS_ID} --exit-code=$?    --message="${DEVICE} just finished deploying all backend projects" ' EXIT

exit 2;

#
eval "$(myops pre-work --response-type=eval)"
myops pre-work --message="${DEVICE} starts to build all backend projects:"
# validate
myops validate --type=device --type=branch --type=docker
# handle
#    just prepare a caches directory of myops
myops checkout-caches
cd "${ENGAGEPLUS_CACHES_REPOSITORY_DIR}"
#    Build Docker images
#        API module
export REPOSITORY=engage-api # to switch repository
myops checkout-caches engage-api-deploy ${API_DEPLOY_BRANCH}
cd "${ENGAGEPLUS_CACHES_DIR}/engage-api-deploy"
chmod u+x ".ops/build-api-docker-image-and-push-to-ECR.sh" && . ".ops/build-api-docker-image-and-push-to-ECR.sh"
myops slack --indent=1 --message="just finished building Docker image of Admin API and Booking API :heavy_check_mark:"
#        Invoice service
export REPOSITORY=invoice-service # to switch repository
myops checkout-caches ${REPOSITORY} ${BRANCH}
cd "${ENGAGEPLUS_CACHES_DIR}/${REPOSITORY}"
chmod u+x ".ops/build-docker-image-and-push-to-ECR.sh" && . ".ops/build-docker-image-and-push-to-ECR.sh"
myops slack --indent=1 --message="just finished building Docker image of Invoice Service :heavy_check_mark:"
#        Payment service
export REPOSITORY=payment-service # to switch repository
myops checkout-caches ${REPOSITORY} ${BRANCH}
cd "${ENGAGEPLUS_CACHES_DIR}/${REPOSITORY}"
chmod u+x ".ops/build-docker-image-and-push-to-ECR.sh" && . ".ops/build-docker-image-and-push-to-ECR.sh"
myops slack --indent=1 --message="just finished building Docker image of Payment Service :heavy_check_mark:"
#        Integration API
export REPOSITORY=integration-api # to switch repository
myops checkout-caches ${REPOSITORY} ${BRANCH}
cd "${ENGAGEPLUS_CACHES_DIR}/${REPOSITORY}"
chmod u+x ".ops/build-docker-image-and-push-to-ECR.sh" && . ".ops/build-docker-image-and-push-to-ECR.sh"
myops slack --indent=1 --message="just finished building Docker image of Integration API :heavy_check_mark:"
#    Deploy ELB
export REPOSITORY=myops # to switch repository
cd "${ENGAGEPLUS_CACHES_REPOSITORY_DIR}" # back to caches directory of myops
myops slack --indent=1 --message="starts to deploying new version to ELB (Elastic Beanstalk) :arrows_counterclockwise:"
myops elb-update-version

# todo
myops sub-title test
