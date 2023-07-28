FROM php:8.2-cli

# Execute during the build of the image
#ARG TEMPIO_VERSION BUILD_ARCH
#RUN \
#    curl -sSLf -o /usr/bin/tempio \
#    "https://github.com/home-assistant/tempio/releases/download/${TEMPIO_VERSION}/tempio_${BUILD_ARCH}"

RUN docker-php-ext-install sockets && \
    docker-php-ext-install pcntl

COPY app /app

EXPOSE 20554

ENTRYPOINT ["/usr/local/bin/php"]
CMD ["/app/start.php"]
