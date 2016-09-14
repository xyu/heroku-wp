#!/bin/bash

#
# Sets up Composer if there's not already a system provided one.
# We symlink to the system provided one so that we have a standard way to
# call composer in this project.
#
# Usage:
# $ ./install-composer.sh
#

# Go to bin dir
cd `dirname $0`

# Cleanup
rm -f composer
rm -f composer-setup.php

# Check to see if composer is system installed
type composer >/dev/null 2>&1 && which composer >/dev/null 2>&1
if [ "$?" -eq "0" ]; then
	echo "Using system-wide composer"
	ln -s `which composer` composer
	exit
fi

# Download and install
echo "Downloading Composer Installer..."
curl https://getcomposer.org/installer > composer-setup.php

# Verify Sig
EXPECTED_SIGNATURE=$( curl -s https://composer.github.io/installer.sig )
ACTUAL_SIGNATURE=$( php -r "echo hash_file('SHA384', 'composer-setup.php');" )

if [ "$EXPECTED_SIGNATURE" = "$ACTUAL_SIGNATURE" ]
then
	php composer-setup.php --filename composer
	RESULT=$?
	rm composer-setup.php
else
	>&2 echo 'ERROR: Invalid installer signature'
	rm composer-setup.php
	exit 1
fi
