#!/bin/bash

#
# Creates a new Heroku app with the given name and adds required add-ons.
#
# Usage:
# $ ./init.sh <APP-NAME>
#

# Check we got a valid new name
if [ -z "$1" ]
then
	echo >&2 "Please specify a name (subdomain) for your new Heroku WP app."
	exit 1
fi

if [[ "$1" =~ [^a-z0-9-]+ ]]
then
	echo >&2 "App name '$1' is invalid."
	exit 1
fi

# Check to see if Heroku Toolbelt is installed
type heroku >/dev/null 2>&1 || {
	echo >&2 "Heroku Toolbelt must be installed. (https://toolbelt.heroku.com)"
	exit 1
}

# Create new app and check for success
heroku apps:create "$1" || {
	echo >&2 "Could not create Heroku WP app."
	exit 1
}

# Add Redis Cache
heroku addons:create \
	--app "$1" \
	heroku-redis:hobby-dev

# Add MySQL DB
heroku addons:create \
	--app "$1" \
	--as "WP_DB" \
	jawsdb-maria:kitefin

heroku config:set \
	--app "$1" \
	WP_DB_SSL="ON"

# Add SendGrid for email
heroku addons:create \
	--app "$1" \
	sendgrid:starter

# Add New Relic for metrics
heroku addons:create \
	--app "$1" \
	newrelic:wayne

heroku config:set \
	--app "$1" \
	NEW_RELIC_APP_NAME="Heroku WP"

# Set WP salts
type dd >/dev/null
if [ "$?" -ne "0" ]; then
	echo "Setting WP salts with WordPress.org"

	heroku config:set \
		--app "$1" \
		$( \
			curl -s 'https://api.wordpress.org/secret-key/1.1/salt/' | \
			sed -E -e "s/^define\('(.+)', *'(.+)'\);$/WP_\1=\2/" -e 's/ //g' \
		)
else
	echo "Setting WP salts with /dev/random"

	heroku config:set \
		--app "$1" \
		WP_AUTH_KEY=$(         dd "if=/dev/random" "bs=1" "count=96" 2>/dev/null | base64 ) \
		WP_SECURE_AUTH_KEY=$(  dd "if=/dev/random" "bs=1" "count=96" 2>/dev/null | base64 ) \
		WP_LOGGED_IN_KEY=$(    dd "if=/dev/random" "bs=1" "count=96" 2>/dev/null | base64 ) \
		WP_NONCE_KEY=$(        dd "if=/dev/random" "bs=1" "count=96" 2>/dev/null | base64 ) \
		WP_AUTH_SALT=$(        dd "if=/dev/random" "bs=1" "count=96" 2>/dev/null | base64 ) \
		WP_SECURE_AUTH_SALT=$( dd "if=/dev/random" "bs=1" "count=96" 2>/dev/null | base64 ) \
		WP_LOGGED_IN_SALT=$(   dd "if=/dev/random" "bs=1" "count=96" 2>/dev/null | base64 ) \
		WP_NONCE_SALT=$(       dd "if=/dev/random" "bs=1" "count=96" 2>/dev/null | base64 )
fi

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

# Create a branch to deploy
git checkout -b "$1"
git push heroku "$1:master"

printf "\n\nNew Heroku WP app '$1' created and deployed via:\n git push heroku $1:master\n\n"
heroku addons --app "$1"
heroku redis --app "$1"
