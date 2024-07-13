#!/bin/bash

set -eu -o pipefail

if [ $# -lt 2 ]
then
	echo "USAGE: start-rt-test.sh <uid> <rt-test-id>"
	echo " - <uid> is your bastion uid you use to log in to scandium/testreduce1002"
	echo " - <rt-test-id> is the test id to show up in the testreduce web UI (usually a 8-char prefix of a git hash)"
	exit 1
fi

uid=$1
testid=$2

# By convention we truncate testids to 8 characters, but some users
# copy-and-paste full git hashes on the command line.  Normalize.
testid=$(echo -n "$testid" | head -c 8)

# Update code on scandium since RT testing scripts will hit the Parsoid REST API on scandium
echo "---- Updating code on scandium ----"
ssh "$uid"@scandium.eqiad.wmnet <<EOF
# No unset vars + early exit on error
set -eu -o pipefail

umask 0002 # Make sure everyone in wikidev group can write
cd /srv/parsoid-testing
git fetch
git checkout $testid
git log --oneline -n 1
sudo systemctl restart php7.4-fpm.service
EOF

echo "---- Starting test run $testid on testreduce1002 ----"
ssh "$uid"@testreduce1002.eqiad.wmnet <<EOF
# No unset vars + early exit on error
set -eu -o pipefail

# Check if we need to free disk space
df /srv/data --output=pcent | tail -n 1 | awk '0+\$1 > 80 {print; print "Free disk space to continue!\nSee https://wikitech.wikimedia.org/wiki/Parsoid/Common_Tasks#Freeing_disk_space"; exit 1}'

echo 'Stopping parsoid-rt clients ...'
sudo service parsoid-rt-client stop

echo 'Updating deploy repo checkout ...'
cd /srv/parsoid-testing

# Strictly speaking, it is not necessary to update code on testreduce1002
# It is only needed if rt-testing related code is updated.
# But, it is simpler to just update it every single time.
umask 0002 # Make sure everyone in wikidev group can write
git fetch
git checkout $testid
git log --oneline -n 1

echo 'Adding new test id ...'
echo $testid > /srv/parsoid-testing/tests/testreduce/parsoid.rt-test.ids

echo 'Starting parsoid-rt clients ...'
sudo service parsoid-rt-client restart

echo "---- Tailing logs from parsoid-rt (^C when you are satisfied) ----"
sudo journalctl -f -u parsoid-rt
EOF
