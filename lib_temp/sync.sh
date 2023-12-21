#!/bin/bash
# usage:
#    sh _ops/lib_temp/sync.sh

#1 === load env to get github token ===
. _ops/lib_temp/load-env-ops.sh

# === validate ===
if [[ "$(php _ops/LIB repository)" == "ops-lib" ]]; then
  echo "[ERROR] detect run to sync in 'ops-lib', should be run in another project";
  exit 0;
fi
echo "validate ok";

# todo
##2 === handle sync library ===
##    move to _ops/lib_temp folder
#SHARED_LIB_DIR="$(php _ops/lib_temp/WorkingDir)/_ops/lib_temp";
#echo "===";
#echo " > Clear directory '${SHARED_LIB_DIR}' and pull new code";
#rm -rf ${SHARED_LIB_DIR}
#mkdir -p ${SHARED_LIB_DIR}
#echo " > Jump to '${SHARED_LIB_DIR}'";
#cd "${SHARED_LIB_DIR}" || exit;
#git clone https://${GITHUB_PERSONAL_ACCESS_TOKEN}@github.com/congnqnexlesoft/ops-lib_temp.git .
##
#php ZHandleSyncIgnore
#
##3  === need to push to Git to update
#echo "===";
#echo "Please review new code and push to GitHub";
#echo "===";
