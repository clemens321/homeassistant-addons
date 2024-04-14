#!/bin/sh

if [ -L /app ]; then
    echo "/app is already a symlink, exiting"
    exit 1
fi
if [ -e /app-prod ]; then
    echo "/app-prod already exists, exiting"
    exit 1
fi

mv /app /app-prod

apt-get update && apt-get install --yes vim
ln -s /addons/clemens321-addons/twitchdl/app /app
cp /addons/clemens321-addons/twitchdl/vimrc /etc/vim/vimrc.local
