#!/bin/bash

# Download release of Oyst PHP SDK

GITHUB_OWNER="oystparis"
GITHUB_REPO="oyst-php"
GITHUB_RELEASE=

# Do not change under this comment

function download_sdk {
    RET=1
    while [ "$RET" -ne "0" ]; do
        echo exit | curl -sL $GITHUB_PROJECT_RELEASE_URL -o $GITHUB_REPO-$GITHUB_RELEASE.tar.gz
        RET=$?
        if [ "$RET" -ne "0" ]; then
            echo "Retry download Oyst SDK."
        fi
       sleep 5
    done
}

SCRIPT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
cd $SCRIPT_DIR
rm -rf $GITHUB_OWNER-$GITHUB_REPO-* $GITHUB_REPO $GITHUB_REPO.tar.gz

if [ -n "$GITHUB_RELEASE" ]; then
    # Specified release url
    GITHUB_PROJECT_RELEASE_URL=https://api.github.com/repos/$GITHUB_OWNER/$GITHUB_REPO/tarball/$GITHUB_RELEASE
else
    # Latest release url
    GITHUB_PROJECT_LATEST_RELEASES_URL=https://api.github.com/repos/$GITHUB_OWNER/$GITHUB_REPO/releases/latest
    GITHUB_PROJECT_RELEASE_URL=$(curl -LsS --connect-timeout 5 -H "Authorization":"token $GITHUB_TOKEN" --max-time 10 --retry 5 --retry-delay 0 --retry-max-time 60 $GITHUB_PROJECT_LATEST_RELEASES_URL | grep 'tarball_url' | cut -d\" -f4)
    GITHUB_RELEASE=$(curl -LsS --connect-timeout 5 -H "Authorization":"token $GITHUB_TOKEN" --max-time 10 --retry 5 --retry-delay 0 --retry-max-time 60 $GITHUB_PROJECT_LATEST_RELEASES_URL | grep 'tag_name' | cut -d\" -f4)
fi

download_sdk
tar -xzf $GITHUB_REPO-$GITHUB_RELEASE.tar.gz
mv $GITHUB_OWNER-$GITHUB_REPO-* $GITHUB_REPO
echo "Oyst SDK $GITHUB_RELEASE is downloaded in $SCRIPT_DIR."

# Composer Install
cd $GITHUB_REPO
composer install --no-dev
echo "Composer install done."
exit 0
