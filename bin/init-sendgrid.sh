#!/bin/bash

#
# Sets up SendGrid API key.
#
# Usage:
# $ ./init-sendgrid.sh <APP-NAME>
#

# Go to bin dir
cd "$(dirname "$0")" || exit

# Check we got a valid new name
if [[ -z "$1" ]]; then
  echo >&2 "Please specify the name (subdomain) for your Heroku WP app."
  exit 1
fi

if [[ "$1" =~ [^a-z0-9-]+ ]]; then
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
  echo >&2 "You don't have access to app '$1'."
  exit 1
}

# Add addon if we need it
heroku addons:info --app "$1" sendgrid >/dev/null 2>&1 || {
  heroku addons:create \
    --app "$1" \
    sendgrid:starter
}

# Get credentials for SendGrid
SENDGRID_USERNAME=$( heroku config:get SENDGRID_USERNAME --app "$1" )
SENDGRID_PASSWORD=$( heroku config:get SENDGRID_PASSWORD --app "$1" )
if [[ -z "$SENDGRID_USERNAME" && -z "$SENDGRID_PASSWORD" ]]; then
  echo >&2 "Can not get SendGrid credentials from app name '$1'."
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
  --app "$1" \
  SENDGRID_API_KEY="$API_KEY"

echo "SendGrid has been successfully setup!"
