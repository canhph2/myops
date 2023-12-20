#!/bin/bash
# usage:
#    sh _ops/lib/sync.sh

#1 === load env to get github token ===
. _ops/lib/load-env-ops.sh


echo "SLACK_CHANNEL=${SLACK_CHANNEL}"
return;

#2 === sync library
#    move to _ops/lib folder
SHARED_LIB_DIR="$(php _ops/lib/WorkingDir)/_ops/lib";
echo "===";
echo " > Clear directory '${SHARED_LIB_DIR}' and pull new code";
rm -rf ${SHARED_LIB_DIR}
mkdir -p ${SHARED_LIB_DIR}
echo " > Jump to '${SHARED_LIB_DIR}'";
cd "${SHARED_LIB_DIR}" || exit;
git clone https://${GITHUB_PERSONAL_ACCESS_TOKEN}@github.com/congnqnexlesoft/ops-lib.git .
#
php ZHandleSyncIgnore

#3  === need to push to Git to update
echo "===";
echo "Please review new code and push to GitHub";
echo "===";
