#!/bin/bash
set -e -o pipefail

#
# Sets up SendGrid API key.
#
# Usage:
# $ ./init-sendgrid.sh <APP-NAME>
#

# Run preflight checks
source "$(dirname ${BASH_SOURCE[0]})/check-prerequisites.sh"

# Check we have access to app
echo "Checking Heroku app permissions"
heroku apps:info --app "$APP" >/dev/null 2>&1 || {
	echo >&2 "Can not update app name '$APP'."
	exit 1
}

# Add addon if we need it
heroku addons:info --app "$APP" sendgrid >/dev/null 2>&1 || {
	heroku addons:create \
		--app "$APP" \
		sendgrid:starter
}

# Get credentials for SendGrid
SENDGRID_USERNAME=$( heroku config:get SENDGRID_USERNAME --app "$APP" )
SENDGRID_PASSWORD=$( heroku config:get SENDGRID_PASSWORD --app "$APP" )
if [[ -z "$SENDGRID_USERNAME" && -z "$SENDGRID_PASSWORD" ]]; then
	echo >&2 "Can not get SendGrid credentials from app name '$APP'."
	exit 1
fi

# Display instructions to get a SendGrid API key
echo "Creating SendGrid API key"
echo -e "\x1B[1;34m?\x1B[0m \x1B[2mIf you're having trouble creating an API key, please read the SendGrid add-on official documentation: \x1B[1;4mhttps://devcenter.heroku.com/articles/sendgrid#obtaining-an-api-key\x1B[0m"
echo -e "\n1) Please browse to \x1B[1;4mhttps://app.sendgrid.com/login?redirect_to=%2Fsettings%2Fapi_keys\x1B[0m"
echo -e "2) Log in using the following credentials: \x1B[1m$SENDGRID_USERNAME\x1B[0m / \x1B[1m$SENDGRID_PASSWORD\x1B[0m"
echo "3) Create an API key with the following permissions:"
echo "    # Mail Send       ➞  FULL ACCESS"
echo "    # Stats           ➞  READ ACCESS"
echo "    # Template Engine ➞  READ or FULL ACCESS"

# Prompt user for API key
read -rp '4) Enter newly created API key: ' API_KEY

# Validate API key input
while [[ ! "$API_KEY" =~ ^SG\.[-_[:alnum:]]+\.[-_[:alnum:]]+$ ]]; do
	read -rp 'Please enter a valid API key (e.g: SG.xxxxxxxx.yyyyyyyy): ' API_KEY
done

# Set SENDGRID_API_KEY env variable on Heroku
heroku config:set \
	--app "$APP" \
	SENDGRID_API_KEY="$API_KEY"

echo "SendGrid has been successfully setup!"
