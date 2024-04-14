FROM jlesage/jdownloader-2:v24.01.1

RUN sed -i 's~/config~/data/jdownloader~g' \
    /startapp.sh \
    /etc/cont-init.d/55-jdownloader2.sh \
    /defaults/default_site.conf \
    /defaults/default_stream.conf \
    && \
    sed -i 's~/output~/media/jdownloader~g' \
    /defaults/cfg/org.jdownloader.gui.views.linkgrabber.addlinksdialog.LinkgrabberSettings.downloaddestinationhistory.json \
    /defaults/cfg/org.jdownloader.settings.GeneralSettings.json \
    /defaults/cfg/org.jdownloader.gui.views.linkgrabber.addlinksdialog.LinkgrabberSettings.json \
    && \
    sed -i 's~^take-ownership.*output$~mkdir -p /media/jdownloader\ntake-ownership --not-recursive /media/jdownloader~' \
    /etc/cont-init.d/55-jdownloader2.sh \
    && echo "Adjusted paths"

ENV USER_ID=0
ENV GROUP_ID=0

COPY rootfs/ /
