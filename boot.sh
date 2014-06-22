#!/usr/bin/env bash

# Build the release
echo "Building release"
cp -R WordPress/* public/.
rm -rf WordPress

# Boot up!
/vendor/bin/heroku-hhvm-nginx public/