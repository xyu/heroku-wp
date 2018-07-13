#!/bin/bash

#
# Sourced by other scripts to check basic prerequisites are met
#

# Name inputs
APP="$1"

# Find root of app
APP_DIR=$( dirname "${BASH_SOURCE[0]}" )
APP_DIR=$( cd "$APP_DIR/.."; pwd )

# Check we got a valid name
if [ -z "$APP" ]
then
	echo >&2 "An app name must be specified for Heroku WP."
	exit 1
fi

if ! [[ "$APP" =~ ^[a-z][a-z0-9-]{2,29}$ ]]
then
	echo >&2 "App name '$APP' is invalid."
	exit 1
fi

# Check to see if PHP is installed
type php >/dev/null 2>&1 || {
	echo >&2 "PHP is required."
	exit 1
}

# Check to see if cURL is installed
type curl >/dev/null 2>&1 || {
	echo >&2 "cURL is required."
	exit 1
}

# Check to see if Composer is installed if not install it
type $APP_DIR/bin/composer >/dev/null 2>&1 || $APP_DIR/bin/init-composer.sh || {
	echo >&2 "Composer does not exist and could not be installed."
	exit 1
}

# Check to see if Heroku Toolbelt is installed
type heroku >/dev/null 2>&1 || {
	echo >&2 "Heroku Toolbelt must be installed. (https://toolbelt.heroku.com)"
	exit 1
}

heroku auth:whoami >/dev/null 2>&1 || {
	echo "Running 'heroku auth:login' to authente account..."
	heroku auth:login
	heroku auth:whoami >/dev/null 2>&1 || {
		echo >&2 "Heroku account required."
		exit 1
	}
}
