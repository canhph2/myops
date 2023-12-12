#!/bin/bash
#set -e # tells the shell to exit if a command returns a non-zero exit status
#set -x # tells the shell to print the commands that are being executed

# usage:
#    . _ops/_shared_lib/_1_LOAD_ENV_OPS.sh

# === get Ops .env on AWS Secret Manager  ===
aws secretsmanager get-secret-value --secret-id env-ops --query SecretString --output text > .env-ops
#    source this .env
. ".env-ops";
#    remove this .env-ops to keep safe
rm -f ".env-ops";
# === END ===

# === load Repository Info ===
export BRANCH=$(php _ops/_shared_lib/BRANCH)
export REPOSITORY=$(php _ops/_shared_lib/REPOSITORY)
export HEAD_COMMIT_ID=$(php _ops/_shared_lib/HEAD_COMMIT_ID)
# === END ===

# === constants ===
export DOCKER_BASE_TAG_PRODUCTION="production"
export DOCKER_BASE_TAG_DEVELOP="develop"

# === engage-api-deploy vars ===
if [ "${BRANCH}" = "develop" ]; then
  export ENV=dev
  export API_DEPLOY_BRANCH=develop-multi-container
  export EB_ENVIRONMENT_NAME="develop-multi-container"
  #
  export COMPOSER_UPDATE="composer update"
  export DOCKER_BASE_TAG="${DOCKER_BASE_TAG_DEVELOP}"
fi
if [ "${BRANCH}" = "staging" ]; then
  export ENV=stg
  export API_DEPLOY_BRANCH=staging-multi-container
  export EB_ENVIRONMENT_NAME="staging-multi-container"
  #
  export COMPOSER_UPDATE="composer update --no-dev --optimize-autoloader --no-scripts"
  export DOCKER_BASE_TAG="${DOCKER_BASE_TAG_PRODUCTION}"
fi
if [ "${BRANCH}" = "master" ]; then
  export ENV=prd
  export API_DEPLOY_BRANCH=master-multi-container
  export EB_ENVIRONMENT_NAME="engageplus-prod-multi-container"
  #
  export COMPOSER_UPDATE="composer update --no-dev --optimize-autoloader --no-scripts"
  export DOCKER_BASE_TAG="${DOCKER_BASE_TAG_PRODUCTION}"
fi
# === END ===

# === AWS Account configuration ===
export AWS_ACCOUNT_ID="982080672983"
export REGION="ap-east-1"
#    ECR configuration
#        base and caches repositories
export ECR_REPO_API_BASE="${AWS_ACCOUNT_ID}.dkr.ecr.${REGION}.amazonaws.com/engageplus-base-api-repository"
export ECR_REPO_PAYMENT_SERVICE_BASE="${AWS_ACCOUNT_ID}.dkr.ecr.${REGION}.amazonaws.com/engageplus-base-payment-service-repository"
export ECR_REPO_INVOICE_SERVICE_BASE="${AWS_ACCOUNT_ID}.dkr.ecr.${REGION}.amazonaws.com/engageplus-base-invoice-service-repository"
export ECR_REPO_INTEGRATION_API_BASE="${AWS_ACCOUNT_ID}.dkr.ecr.${REGION}.amazonaws.com/engageplus-base-integration-api-repository"
export ECR_REPO_INVOICE_SERVICE_BASE="${AWS_ACCOUNT_ID}.dkr.ecr.${REGION}.amazonaws.com/engageplus-base-email-service-repository"
#        normal repositories
export ECR_REPO_API="${AWS_ACCOUNT_ID}.dkr.ecr.${REGION}.amazonaws.com/engageplus-${ENV}-api-repository"
export ECR_REPO_PAYMENT_SERVICE="${AWS_ACCOUNT_ID}.dkr.ecr.${REGION}.amazonaws.com/engageplus-${ENV}-payment-service-repository"
export ECR_REPO_INVOICE_SERVICE="${AWS_ACCOUNT_ID}.dkr.ecr.${REGION}.amazonaws.com/engageplus-${ENV}-invoice-service-repository"
export ECR_REPO_INTEGRATION_API="${AWS_ACCOUNT_ID}.dkr.ecr.${REGION}.amazonaws.com/engageplus-${ENV}-integration-api-repository"
export ECR_REPO_INVOICE_SERVICE="${AWS_ACCOUNT_ID}.dkr.ecr.${REGION}.amazonaws.com/engageplus-${ENV}-email-service-repository"
#    Elastic Beanstalk configuration
export S3_EB_APP_VERSION_BUCKET_NAME="elasticbeanstalk-${REGION}-${AWS_ACCOUNT_ID}"
export EB_APP_VERSION_FOLDER_NAME="engageplus"
export EB_APP_NAME="engageplus"
# === END ===

# === EngagePlus configuration ===
export ENGAGEPLUS_CACHES_FOLDER=".caches_engageplus"
export ENGAGEPLUS_CACHES_DIR="$(php _ops/_shared_lib/HOME_DIR)/${ENGAGEPLUS_CACHES_FOLDER}"
export ENGAGEPLUS_CACHES_REPOSITORY_DIR="${ENGAGEPLUS_CACHES_DIR}/${REPOSITORY}"
# === END ===

# === get DEVICE from param 1 ===
export DEVICE="$1"
# === END ===
