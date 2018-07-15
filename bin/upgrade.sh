#!/bin/bash
set -e -o pipefail

#
# Merge latest changes from upstream into the current branch.
#
# Usage:
# $ ./upgrade.sh [no-lock]
#

# Find root of app
APP_DIR=$( dirname "${BASH_SOURCE[0]}" )
APP_DIR=$( cd "$APP_DIR/.."; pwd )

# Go to root dir
cd "$APP_DIR"

# Check Prerequisites
bin/composer --version > /dev/null

# Sets the upstream branch if non exists
git remote show upstream >/dev/null 2>&1 || {
	echo "Adding a git remote for upstream repo (https://github.com/xyu/heroku-wp)."
	git remote add upstream https://github.com/xyu/heroku-wp.git
}

# Stash changes
git stash

# Merge in latest change from upstream
git fetch upstream
git merge --no-commit --squash --no-ff upstream/master

# Maybe rebuild composer lock file
if [ "$2" = "no-lock" ]; then
	echo "Skipping building of composer.lock..."
else
	bin/composer update --ignore-platform-reqs
fi

# Show changes
echo "Merged changes from upstream:"
git status

# Apply local changes
echo "Applying local (uncommitted) changes"
git stash pop
