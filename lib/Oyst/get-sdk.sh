#!/bin/bash

# Download release of Oyst PHP SDK

GITHUB_OWNER="oystparis"
GITHUB_REPO="oyst-php"
GITHUB_BRANCH=
GITHUB_RELEASE=

# Do not change under this comment

function download_sdk {
    RET=1
    while [ "$RET" -ne "0" ]; do
        echo exit | curl -sL "$GITHUB_PROJECT_RELEASE_URL" -o $GITHUB_REPO-$GITHUB_RELEASE.tar.gz
        RET=$?
        if [ "$RET" -ne "0" ]; then
            echo "Retry download Oyst SDK."
            echo "Please set GITHUB_TOKEN environment variable."
        fi
       sleep 5
    done
}

function installRelease {
    download_sdk
    tar -xzf "$GITHUB_REPO-$GITHUB_RELEASE.tar.gz"
    mv $GITHUB_OWNER-$GITHUB_REPO-* $GITHUB_REPO
    echo "Oyst SDK $GITHUB_RELEASE is downloaded in $SCRIPT_DIR."
}

SCRIPT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
cd "$SCRIPT_DIR" || exit
rm -rf $GITHUB_OWNER-$GITHUB_REPO-* $GITHUB_REPO $GITHUB_REPO.tar.gz

if [ -n "$GITHUB_BRANCH" ]; then
    # Specified git branch
    git clone --depth=1 --branch=$GITHUB_BRANCH https://github.com/$GITHUB_OWNER/$GITHUB_REPO.git $GITHUB_REPO
    rm -rf !$/.git

elif [ -n "$GITHUB_RELEASE" ]; then
    # Specified release url
    GITHUB_PROJECT_RELEASE_URL=https://api.github.com/repos/$GITHUB_OWNER/$GITHUB_REPO/tarball/$GITHUB_RELEASE
    installRelease

else
    # Latest release url
    GITHUB_PROJECT_LATEST_RELEASES_URL=https://api.github.com/repos/$GITHUB_OWNER/$GITHUB_REPO/releases/latest

    if [ -n "$GITHUB_TOKEN" ]; then
        GITHUB_PROJECT_RELEASE_URL=$(curl -LsS --connect-timeout 5 -H "Authorization":"token $GITHUB_TOKEN" --max-time 10 --retry 5 --retry-delay 0 --retry-max-time 60 $GITHUB_PROJECT_LATEST_RELEASES_URL | grep 'tarball_url' | cut -d\" -f4)
        GITHUB_RELEASE=$(curl -LsS --connect-timeout 5 -H "Authorization":"token $GITHUB_TOKEN" --max-time 10 --retry 5 --retry-delay 0 --retry-max-time 60 $GITHUB_PROJECT_LATEST_RELEASES_URL | grep 'tag_name' | cut -d\" -f4)
        installRelease
    else
        GITHUB_PROJECT_RELEASE_URL=$(curl -LsS --connect-timeout 5 --max-time 10 --retry 5 --retry-delay 0 --retry-max-time 60 $GITHUB_PROJECT_LATEST_RELEASES_URL | grep 'tarball_url' | cut -d\" -f4)
        GITHUB_RELEASE=$(curl -LsS --connect-timeout 5 --max-time 10 --retry 5 --retry-delay 0 --retry-max-time 60 $GITHUB_PROJECT_LATEST_RELEASES_URL | grep 'tag_name' | cut -d\" -f4)
        installRelease
    fi
fi

# Composer Install
cd "$GITHUB_REPO" || exit
composer install --no-dev -o
echo "Composer install done."
exit 0
