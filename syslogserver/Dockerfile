FROM pbertera/syslogserver

RUN mv /var/log/net /data/log-net \
    && ln -sf /data/log-net/ /var/log/net \
    && echo "Adjusted paths"

ENV SYSLOG_USERNAME=admin
ENV SYSLOG_PASSWORD=admin

COPY rootfs/ /
