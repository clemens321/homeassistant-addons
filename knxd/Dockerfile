# https://developers.home-assistant.io/docs/add-ons/configuration#add-on-dockerfile
ARG BUILD_FROM
FROM $BUILD_FROM as builder

# Choose between branches
ARG KNXD_BRANCH=v0.14

# Based on https://github.com/boomalien/alpine-knxd.git
# Using multi-stage build to get a clean image - I don't like to install, use and remove the dev environment in a RUN
# removed dependencies: abuild binutils dev86 // because:
# added bsd-compat-headers (required from dev86 dependencies)
# added pkgconf (required from abuild dependencies)
# removed configure flags (didn't miss these): --enable-eibnetip --enable-eibnetserver --enable-eibnetiptunnel

RUN apk add --no-cache \
        argp-standalone \
        autoconf \
        automake \
        bsd-compat-headers \
        build-base \
        cmake \
        git \
        libev-dev \
        libtool \
        libusb-dev \
        linux-headers \
        pkgconf \
    && \
    git clone https://github.com/knxd/knxd.git --single-branch --branch $KNXD_BRANCH /root/knxd && \
    cd /root/knxd && \
    ./bootstrap.sh && \
    ./configure --disable-systemd && \
    make -j$(nproc) && \
    make install

FROM $BUILD_FROM

# Execute during the build of the image
ARG TEMPIO_VERSION BUILD_ARCH
RUN \
    curl -sSLf -o /usr/bin/tempio \
    "https://github.com/home-assistant/tempio/releases/download/${TEMPIO_VERSION}/tempio_${BUILD_ARCH}"

RUN apk add --no-cache \
        libstdc++ \
        libev \
        libusb \
    && true
# ... final `true` to prevent edit errors with `\` at the line ending.

COPY --from=builder /usr/local /usr/local

# Copy root filesystem
COPY rootfs /
