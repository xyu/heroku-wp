#!/bin/bash
set -e -o pipefail

#
# Resets WP salts
#
# Usage:
# $ ./update-salts.sh <APP-NAME>
#

# Run preflight checks
source "$(dirname ${BASH_SOURCE[0]})/check-prerequisites.sh"

# Check we have access to app
echo "Checking Heroku app permissions"
heroku apps:info --app "$APP" >/dev/null 2>&1 || {
	echo >&2 "Can not update app name '$APP'."
	exit 1
}

# Set WP salts
type dd >/dev/null
if [ "$?" -ne "0" ]; then
	echo "Setting WP salts with WordPress.org"

	heroku config:set \
		--app "$APP" \
		$( \
			curl -s 'https://api.wordpress.org/secret-key/1.1/salt/' | \
			sed -E -e "s/^define\('(.+)', *'(.+)'\);$/WP_\1=\2/" -e 's/ //g' \
		)
else
	echo "Setting WP salts with /dev/random"

	heroku config:set \
		--app "$APP" \
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
