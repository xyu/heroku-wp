#!/bin/bash
set -e -o pipefail

#
# Creates a new Heroku app with the given name and adds required add-ons.
#
# Usage:
# $ ./init.sh <APP-NAME>
#

# Run preflight checks
source "$(dirname ${BASH_SOURCE[0]})/check-prerequisites.sh"

echo "Provisioning Heroku WP via app.json..."
curl -n -s \
	-X POST https://api.heroku.com/app-setups \
	-H "Accept: application/vnd.heroku+json; version=3" \
	-H "Content-Type: application/json" \
	-d '{
		"app": {
			"name": "'$APP'"
		},
		"source_blob": {
			"url": "https://github.com/xyu/heroku-wp/tarball/button"
		}
	}'
printf "\n\n" && sleep 10

# Check we have access to app
echo "Checking Heroku app..."
heroku apps:info --app "$APP" >/dev/null 2>&1 || {
	echo >&2 "Can not update app name '$APP'."
	exit 1
}

# Configure Redis Cache
printf "Waiting for Heroku Redis to provision... "
heroku redis:wait \
	--app "$APP"
echo "done"

heroku redis:maxmemory \
	--app "$APP" \
	--policy volatile-lru
heroku redis:timeout \
	--app "$APP" \
	--seconds 60

#
# Do the intial commit for this site
#

# Force heroku git remote to our app
heroku git:remote \
	--app "$APP"

# Make initial commit and deploy
true && \
	cd $APP_DIR && \
	git checkout -b "$APP" && \
	bin/composer update --ignore-platform-reqs && \
	git add composer.lock && \
	git commit -m "Initial commit for '$APP'" && \
	git push heroku "$APP:master"

EXIT_CODE="$?"
if [ "$EXIT_CODE" -ne "0" ]; then
	printf >&2 "\n\nDeploy failed for '$APP'.\n\n"
else
	printf "\n\nNew Heroku WP app '$APP' created and deployed via:\n\$ git push heroku $APP:master\n\n"
fi

heroku addons --app "$APP"
heroku redis --app "$APP"

exit "$EXIT_CODE"
