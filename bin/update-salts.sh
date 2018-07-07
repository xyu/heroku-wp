#!/bin/bash

#
# Resets WP salts
#
# Usage:
# $ ./update-salts.sh <APP-NAME>
#

# Go to bin dir
cd `dirname $0`

# Check we got a valid new name
if [ -z "$1" ]
then
	echo >&2 "Please specify the name (subdomain) for your Heroku WP app."
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

# Check we have access to app
echo "Checking Heroku app permissions"
heroku info --app "$1" >/dev/null || {
	echo >&2 "Can not update app name '$1'."
	exit 1
}

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
		WP_AUTH_KEY="$(         dd 'if=/dev/random' 'bs=1' 'count=96' 2>/dev/null | base64 )" \
		WP_SECURE_AUTH_KEY="$(  dd 'if=/dev/random' 'bs=1' 'count=96' 2>/dev/null | base64 )" \
		WP_LOGGED_IN_KEY="$(    dd 'if=/dev/random' 'bs=1' 'count=96' 2>/dev/null | base64 )" \
		WP_NONCE_KEY="$(        dd 'if=/dev/random' 'bs=1' 'count=96' 2>/dev/null | base64 )" \
		WP_AUTH_SALT="$(        dd 'if=/dev/random' 'bs=1' 'count=96' 2>/dev/null | base64 )" \
		WP_SECURE_AUTH_SALT="$( dd 'if=/dev/random' 'bs=1' 'count=96' 2>/dev/null | base64 )" \
		WP_LOGGED_IN_SALT="$(   dd 'if=/dev/random' 'bs=1' 'count=96' 2>/dev/null | base64 )" \
		WP_NONCE_SALT="$(       dd 'if=/dev/random' 'bs=1' 'count=96' 2>/dev/null | base64 )"
fi

echo "Success: WP salts updated."
