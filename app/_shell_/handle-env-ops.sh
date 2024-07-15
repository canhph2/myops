# === REQUIRED: get env-ops and append to this file

# === load Repository Info ===
export BRANCH=$(myops branch)
export REPOSITORY=$(myops repository)
export HEAD_COMMIT_ID=$(myops head-commit-id)
# === END ===

# === constants ===
export DOCKER_BASE_TAG_PRODUCTION="production"
export DOCKER_BASE_TAG_DEVELOP="develop"
export DOCKER_BASE_TAG_OFFICIAL="official"
#    WARNING: delete 'auth.json' after use this command 'COMPOSER_CONFIG_GITHUB_TOKEN'
export COMPOSER_CONFIG_GITHUB_TOKEN="composer config github-oauth.github.com ${GITHUB_PERSONAL_ACCESS_TOKEN}"
export COMPOSER_CONFIG_ALLOW_PLUGINS_SYMFONY_FLEX="composer config --no-plugins allow-plugins.symfony/flex true"
export COMPOSER_INSTALL_DEVELOP="composer install"
export COMPOSER_INSTALL_DEVELOP_TO_BUILD_CACHES="composer install --no-autoloader --no-scripts --no-plugins"
export COMPOSER_INSTALL_PRODUCTION="composer install --no-dev --optimize-autoloader"
export COMPOSER_INSTALL_PRODUCTION_TO_BUILD_CACHES="composer install --no-dev --no-autoloader --no-scripts --no-plugins"

# === handle branches vars ===
if [ "${BRANCH}" = "develop" ]; then
  export ENV=dev
  export API_DEPLOY_BRANCH=develop-multi-container
  export EB_ENVIRONMENT_NAME="develop-multi-container"
  export EB_2ND_DISK_SIZE="20"
  export EB_MAIL_CATCHER_PORT=",{ \"hostPort\": 1025, \"containerPort\": 1025 }" # maybe remove after email-service
  export ENV_URL_PREFIX="${BRANCH}-"
  #
  export COMPOSER_INSTALL="${COMPOSER_INSTALL_DEVELOP}"
  export DOCKER_BASE_TAG="${DOCKER_BASE_TAG_DEVELOP}"
  export DOCKER_BASE_TAG_API="${DOCKER_BASE_TAG_DEVELOP}" # maybe remove after email-service
  #
  export EMAIL_SERVICE_EXTERNAL_PORT=10000
  export EMAIL_SERVICE_CONTAINER_PORT=80
fi
if [ "${BRANCH}" = "staging" ]; then
  export ENV=stg
  export API_DEPLOY_BRANCH=staging-multi-container
  export EB_ENVIRONMENT_NAME="staging-multi-container"
  export EB_2ND_DISK_SIZE="20"
  export EB_MAIL_CATCHER_PORT=",{ \"hostPort\": 1025, \"containerPort\": 1025 }" # maybe remove after email-service
  export ENV_URL_PREFIX="${BRANCH}-"
  #
  export COMPOSER_INSTALL="${COMPOSER_INSTALL_PRODUCTION}"
  export DOCKER_BASE_TAG="${DOCKER_BASE_TAG_PRODUCTION}"
  export DOCKER_BASE_TAG_API="${DOCKER_BASE_TAG_DEVELOP}" # maybe remove after email-service
  #
  export EMAIL_SERVICE_EXTERNAL_PORT=10001
  export EMAIL_SERVICE_CONTAINER_PORT=80
fi
if [ "${BRANCH}" = "master" ]; then
  export ENV=prd
  export API_DEPLOY_BRANCH=master-multi-container
  export EB_ENVIRONMENT_NAME="engageplus-prod-multi-container"
  export EB_2ND_DISK_SIZE="100"
  export EB_MAIL_CATCHER_PORT="    " # maybe remove after email-service | 4 spaces to pass empty string
  export ENV_URL_PREFIX=""
  #
  export COMPOSER_INSTALL="${COMPOSER_INSTALL_PRODUCTION}"
  export DOCKER_BASE_TAG="${DOCKER_BASE_TAG_PRODUCTION}"
  export DOCKER_BASE_TAG_API="${DOCKER_BASE_TAG_PRODUCTION}" # maybe remove after email-service
  #
  export EMAIL_SERVICE_EXTERNAL_PORT=10002
  export EMAIL_SERVICE_CONTAINER_PORT=80
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
export ECR_REPO_OTHERS_SERVICE="${AWS_ACCOUNT_ID}.dkr.ecr.${REGION}.amazonaws.com/engageplus-others-service-repository"
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
export ENGAGEPLUS_CACHES_DIR="$(myops home-dir)/${ENGAGEPLUS_CACHES_FOLDER}"
export ENGAGEPLUS_CACHES_REPOSITORY_DIR="${ENGAGEPLUS_CACHES_DIR}/${REPOSITORY}"
# === END ===

# === get DEVICE from param 1 ===
export DEVICE="$1"
# === END ===
