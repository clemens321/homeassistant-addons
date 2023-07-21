#!/bin/sh

set -e # Exit immediately if a command exits with a non-zero status.
set -u # Treat unset variables as an error.

# Make sure mandatory directories exist.
mkdir -p /share/jdownloader/logs

if [ ! -d /config/jdownloader ]; then
    cp -r /defaults/cfg /config/jdownloader
fi

if [ ! -d /data/jdownloader ]; then
    mkdir -p /data/jdownloader
    ln -s /config/jdownloader/ /data/jdownloader/cfg
    ln -s /share/jdownloader/logs /data/jdownloader/logs
fi

if [ ! -f /data/jdownloader/JDownloader.jar ]; then
    cp /defaults/JDownloader.jar /data/jdownloader/
fi

