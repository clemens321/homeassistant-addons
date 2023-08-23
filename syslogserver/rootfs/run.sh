#!/bin/bash

set -e

mkdir -p /data
if [ -d /data/log-net ]; then
    rm -r /var/log/net
else
    mv /var/log/net /data/log-net
fi
ln -sf /data/log-net/ /var/log/net

if [ -z $SYSLOG_USERNAME ];then
    export SYSLOG_USERNAME=admin
fi
if [ -z $SYSLOG_PASSWORD ];then
    export SYSLOG_PASSWORD=SyslogP4ss
fi

htpasswd -c -b /etc/nginx/.htpasswd $SYSLOG_USERNAME $SYSLOG_PASSWORD

cd /var/www
php5 -f create-user.php
chown www-data:www-data config.auth.user.php
rm -f create-user.php
cd
supervisord
