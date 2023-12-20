#!/bin/bash
# usage:
#    . _ops/lib/load-env-ops.sh

# === get Ops .env on AWS Secret Manager  ===
aws secretsmanager get-secret-value --secret-id env-ops --query SecretString --output text > .env-ops
#    source this .env
. ".env-ops"
#    remove this .env-ops to keep safe
rm -f ".env-ops"
# === END ===

# === load Repository Info ===
export BRANCH=$(php _ops/lib/Branch)
export REPOSITORY=$(php _ops/lib/Repository)
export HEAD_COMMIT_ID=$(php _ops/lib/HeadCommitID)
# === END ===

# === constants ===
export DOCKER_BASE_TAG_PRODUCTION="production"
export DOCKER_BASE_TAG_DEVELOP="develop"
#    WARNING: delete 'auth.json' after use this command 'COMPOSER_CONFIG_GITHUB_TOKEN'
export COMPOSER_CONFIG_GITHUB_TOKEN="composer config github-oauth.github.com ${GITHUB_PERSONAL_ACCESS_TOKEN}"
export COMPOSER_CONFIG_ALLOW_PLUGINS_SYMFONY_FLEX="composer config --no-plugins allow-plugins.symfony/flex true"
export COMPOSER_INSTALL_DEVELOP="composer install"
export COMPOSER_INSTALL_DEVELOP_TO_BUILD_CACHES="composer install --no-autoloader --no-scripts --no-plugins"
export COMPOSER_INSTALL_PRODUCTION="composer install --no-dev --optimize-autoloader"
export COMPOSER_INSTALL_PRODUCTION_TO_BUILD_CACHES="composer install --no-dev --no-autoloader --no-scripts --no-plugins"

# === engage-api-deploy vars ===
if [ "${BRANCH}" = "develop" ]; then
  export ENV=dev
  export API_DEPLOY_BRANCH=develop-multi-container
  export EB_ENVIRONMENT_NAME="develop-multi-container"
  export ENV_URL_PREFIX="${BRANCH}-"
  #
  export COMPOSER_INSTALL="${COMPOSER_INSTALL_DEVELOP}"
  export DOCKER_BASE_TAG="${DOCKER_BASE_TAG_DEVELOP}"
  export DOCKER_BASE_TAG_API="${DOCKER_BASE_TAG_DEVELOP}" # maybe remove after email-service
fi
if [ "${BRANCH}" = "staging" ]; then
  export ENV=stg
  export API_DEPLOY_BRANCH=staging-multi-container
  export EB_ENVIRONMENT_NAME="staging-multi-container"
  export ENV_URL_PREFIX="${BRANCH}-"
  #
  export COMPOSER_INSTALL="${COMPOSER_INSTALL_PRODUCTION}"
  export DOCKER_BASE_TAG="${DOCKER_BASE_TAG_PRODUCTION}"
  export DOCKER_BASE_TAG_API="${DOCKER_BASE_TAG_DEVELOP}" # maybe remove after email-service
fi
if [ "${BRANCH}" = "master" ]; then
  export ENV=prd
  export API_DEPLOY_BRANCH=master-multi-container
  export EB_ENVIRONMENT_NAME="engageplus-prod-multi-container"
  export ENV_URL_PREFIX=""
  #
  export COMPOSER_INSTALL="${COMPOSER_INSTALL_PRODUCTION}"
  export DOCKER_BASE_TAG="${DOCKER_BASE_TAG_PRODUCTION}"
  export DOCKER_BASE_TAG_API="${DOCKER_BASE_TAG_PRODUCTION}" # maybe remove after email-service
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
export ECR_REPO_EMAIL_SERVICE_BASE="${AWS_ACCOUNT_ID}.dkr.ecr.${REGION}.amazonaws.com/engageplus-base-email-service-repository"
#        normal repositories
export ECR_REPO_API="${AWS_ACCOUNT_ID}.dkr.ecr.${REGION}.amazonaws.com/engageplus-${ENV}-api-repository"
export ECR_REPO_PAYMENT_SERVICE="${AWS_ACCOUNT_ID}.dkr.ecr.${REGION}.amazonaws.com/engageplus-${ENV}-payment-service-repository"
export ECR_REPO_INVOICE_SERVICE="${AWS_ACCOUNT_ID}.dkr.ecr.${REGION}.amazonaws.com/engageplus-${ENV}-invoice-service-repository"
export ECR_REPO_INTEGRATION_API="${AWS_ACCOUNT_ID}.dkr.ecr.${REGION}.amazonaws.com/engageplus-${ENV}-integration-api-repository"
export ECR_REPO_EMAIL_SERVICE="${AWS_ACCOUNT_ID}.dkr.ecr.${REGION}.amazonaws.com/engageplus-${ENV}-email-service-repository"
#    Elastic Beanstalk configuration
export S3_EB_APP_VERSION_BUCKET_NAME="elasticbeanstalk-${REGION}-${AWS_ACCOUNT_ID}"
export EB_APP_VERSION_FOLDER_NAME="engageplus"
export EB_APP_NAME="engageplus"
# === END ===

# === EngagePlus configuration ===
export ENGAGEPLUS_CACHES_FOLDER=".caches_engageplus"
export ENGAGEPLUS_CACHES_DIR="$(php _ops/lib/HomeDir)/${ENGAGEPLUS_CACHES_FOLDER}"
export ENGAGEPLUS_CACHES_REPOSITORY_DIR="${ENGAGEPLUS_CACHES_DIR}/${REPOSITORY}"
# === END ===

# === get DEVICE from param 1 ===
export DEVICE="$1"
# === END ===
