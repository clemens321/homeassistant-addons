FROM php:8.3

RUN apt-get update && apt-get install --yes --no-install-recommends \
    git \
    procps \
    yt-dlp \
    && rm -rf /var/cache/apt/lists

COPY app /app
RUN cd /app; COMPOSER_ALLOW_SUPERUSER=1 ./bin/composer.phar install

COPY php.ini /usr/local/etc/php/conf.d/

ENV PHP_CLI_SERVER_WORKERS=16
ENTRYPOINT ["/usr/local/bin/php"]
CMD ["-S", "0.0.0.0:8080", "/app/start.php"]
