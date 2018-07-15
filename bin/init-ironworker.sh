#!/bin/bash
set -e -o pipefail

#
# Sets up IronWorker to run wp-cron.
# Pass optional 'upgrade' param to upgrade IronWorker code only
#
# Usage:
# $ ./init-ironworker.sh <APP-NAME> [upgrade]
#

# Run preflight checks
source "$(dirname ${BASH_SOURCE[0]})/check-prerequisites.sh"

# Check we have access to app
echo "Checking Heroku app permissions"
heroku apps:info --app "$APP" >/dev/null 2>&1 || {
	echo >&2 "Can not update app name '$APP'."
	exit 1
}

# Check to see if Iron CLI is installed
type iron >/dev/null 2>&1 || {
	echo >&2 "Iron CLI must be installed. (https://github.com/iron-io/ironcli)"
	exit 1
}

# Check to see if NPM is installed
type npm >/dev/null 2>&1 || {
	echo >&2 "NPM must be installed. (https://docs.npmjs.com/getting-started/installing-node)"
	exit 1
}

# Add addon if we need it
heroku addons:info --app "$APP" iron_worker >/dev/null 2>&1 || {
	heroku addons:create \
		--app "$APP" \
		iron_worker:sandbox
}

# Get keys for IronWorker
IRON_PROJECT_ID=$( heroku config:get IRON_WORKER_PROJECT_ID --app "$APP" )
IRON_TOKEN=$( heroku config:get IRON_WORKER_TOKEN --app "$APP" )
if [ -n "$IRON_PROJECT_ID" -a -n "$IRON_TOKEN" ]
then
	echo "Got IronWorker keys from app name '$APP'."
else
	echo >&2 "Can not get IronWorker keys from app name '$APP'."
	exit 1
fi

# Package worker
true && \
	rm -rf iron-worker.tmp && \
	mkdir iron-worker.tmp && \
	cp -R iron-worker/* iron-worker.tmp && \
	sed "s/{HEROKU_SLUG}/$APP/" iron-worker/config.js > iron-worker.tmp/config.js && \
	cd iron-worker.tmp && \
	npm install && \
	zip -r wp-cron-runner.zip . >/dev/null && \
	cd ..

if [ "$?" -ne "0" ]; then
	echo >&2 "Could not package worker."
	exit 1
fi

# Upload worker
IRON_PROJECT_ID="$IRON_PROJECT_ID" IRON_TOKEN="$IRON_TOKEN" \
	iron worker upload \
		--name "wp-cron-runner" \
		--zip "iron-worker.tmp/wp-cron-runner.zip" \
		iron/node "node wp-cron-runner.js"

# Cleanup
rm -rf iron-worker.tmp

if [ "$?" -ne "0" ]; then
	echo >&2 "Could not upload worker."
	exit 1
fi

if [ "$2" = "upgrade" ]; then
	echo "IronWorker code upgraded skipping scheduling of task."
	exit 0
fi

# Schedule worker
IRON_PROJECT_ID="$IRON_PROJECT_ID" IRON_TOKEN="$IRON_TOKEN" \
	iron worker schedule \
		--run-every 1800 \
		--timeout 30 \
		--priority 0 \
		wp-cron-runner

if [ "$?" -ne "0" ]; then
	echo >&2 "Could not schedule worker."
	exit 1
fi

# Turn off WP cron
heroku config:set \
	--app "$APP" \
	DISABLE_WP_CRON="TRUE"

echo "Success: WP Cron scheduled via IronWorker."
