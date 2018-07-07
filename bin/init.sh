#!/bin/bash

#
# Creates a new Heroku app with the given name and adds required add-ons.
#
# Usage:
# $ ./init.sh <APP-NAME>
#

# Go to bin dir
cd `dirname $0`

# Name inputs
APP="$1"

# Run preflight checks
source check-prerequisites.sh

printf "Provisioning Heroku WP via app.json... "
curl -n \
	-X POST https://api.heroku.com/app-setups \
	-H "Accept: application/vnd.heroku+json; version=3" \
	-H "Content-Type: application/json" \
	-d '{
		"app": {
			"name": "'$1'"
		},
		"source_blob": {
			"url": "https://github.com/xyu/heroku-wp/tarball/button"
		}
	}' >/dev/null 2>&1 && sleep 10

# Configure Redis Cache
printf "Waiting for Heroku Redis to provision... "
heroku redis:wait \
	--app "$1"
echo "done"

heroku redis:maxmemory \
	--app "$1" \
	--policy volatile-lru
heroku redis:timeout \
	--app "$1" \
	--seconds 60

#
# Do the intial commit for this site
#

# Force heroku git remote to our app
heroku git:remote \
	--app "$1"

# Make initial commit and deploy
true && \
	cd .. && \
	git checkout -b "$1" && \
	bin/composer update --ignore-platform-reqs && \
	git add composer.lock && \
	git commit -m "Initial commit for '$1'" && \
	git push heroku "$1:master"

EXIT_CODE="$?"
if [ "$EXIT_CODE" -ne "0" ]; then
	printf >&2 "\n\nDeploy failed for '$1'.\n\n"
else
	printf "\n\nNew Heroku WP app '$1' created and deployed via:\n\$ git push heroku $1:master\n\n"
fi

heroku addons --app "$1"
heroku redis --app "$1"

exit "$EXIT_CODE"
