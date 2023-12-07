#!/bin/bash
#set -e # tells the shell to exit if a command returns a non-zero exit status
#set -x # tells the shell to print the commands that are being executed

# usage:
#    sh _ops/_shared_lib/_0_SYNC_CODE_MANUALLY.sh

#1 === load env to get github token ===
. _ops/_shared_lib/_1_LOAD_ENV_OPS.sh

#2 === sync library
#    move to _shared_lib folder
SHARED_LIB_DIR="$(php _ops/_shared_lib/WORKING_DIR)/_ops/_shared_lib";
echo "===";
echo " > Clear directory '${SHARED_LIB_DIR}' and pull new code";
rm -rf ${SHARED_LIB_DIR}
mkdir -p ${SHARED_LIB_DIR}
echo " > Jump to '${SHARED_LIB_DIR}'";
cd "${SHARED_LIB_DIR}" || exit;
git clone https://${GITHUB_PERSONAL_ACCESS_TOKEN}@github.com/infohkengage/engage-api.git .
#   remove some git files
rm -rf .git
rm -f .gitignore

#3  === need to push to Git to update
echo "===";
echo "Please review new code and push to GitHub";
echo "===";
