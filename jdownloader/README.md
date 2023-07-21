# Home Assistant Add-On for JDownloader 2

Based on the great image by [https://github.com/jlesage/docker-jdownloader-2](@jlesage)

## Click'n'Load
To use Click'n'Load from your workstation with this jdownloader you have to

1. Go to JDownloader Settings => Advanced Settings, search for remoteapi.externinterfacelocalhostonly and disable it
2. Defined port 9666 in the addon config
3. Restart the addon
4. Forward 9666 from your workstation to home assistant, e.g. using socat

OR use myjdownloader and an appropriate browser plugin

## Changes
Modifies some paths to be compatible with the home assistant add-on volume layout and adds a config file.

The primary installation moves from /config to /data/jdownloader. /config is reserved as the global home assistant config directory (if mapped, which we want).
/data is a persistent add-on specific volume.
JDownloader's config files (`cfg` subdirectory) is initialized at /config/jdownloader and symlinked as /data/jdownloader/cfg
Log files will be saved at `/share/jdownloader/logs`, symlinked to /data/jdownloader/logs as well
Default output directory will be /media/jdownloader

## Anonymous volumes
Since the origin image marks /output as VOLUME, docker creates an anonymous volume at every start. This folder is not used and remains empty but is left behind if the container stops.
If you want do clean them use for example the Advanced SSH & Web Terminal with protected mode disabled and run `docker volume prune`.
