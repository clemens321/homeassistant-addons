FROM pbertera/syslogserver

#RUN rmdir /var/log/net \
#    && ln -sf /data/log-net/ /var/log/net \
#    && echo "Adjusted paths"

ENV SYSLOG_USERNAME=admin
ENV SYSLOG_PASSWORD=admin

COPY rootfs/ /
