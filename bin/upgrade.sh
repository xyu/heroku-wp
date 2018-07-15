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

# Set stash message
STASH_MSG="Pre Heroku WP Upstream Merge -- $(date)"

# Check Prerequisites
bin/composer --version > /dev/null

# Sets the upstream branch if non exists
git remote show upstream >/dev/null 2>&1 || {
	echo "Adding a git remote for upstream repo (https://github.com/xyu/heroku-wp)."
	git remote add upstream https://github.com/xyu/heroku-wp.git
}

# Stash changes
git stash save --quiet --include-untracked "$STASH_MSG"

# Merge in latest change from upstream
git fetch upstream
git merge --no-commit --no-ff upstream/master

# Maybe rebuild composer lock file
if [ "$1" = "no-lock" ]; then
	echo "Skipping building of composer.lock..."
else
	bin/composer update --ignore-platform-reqs
	git add composer.lock
fi

git commit --message="Upgraded Heroku WP from Upstream"

# Maybe pop stash
if [[ $(git stash list | head -n 1) =~ "$STASH_MSG" ]]; then
	git stash pop --quiet stash@{0}
fi
