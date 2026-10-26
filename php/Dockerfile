FROM php:8.4-alpine

# Set timezone to Asia/Tokyo
RUN apk add --no-cache tzdata && \
    cp /usr/share/zoneinfo/Asia/Tokyo /etc/localtime && \
    echo "Asia/Tokyo" > /etc/timezone && \
    apk del tzdata && \
    rm -rf /var/cache/apk/*

# Install necessary dependencies and PHP extensions
RUN apk add --no-cache --virtual .build-deps $PHPIZE_DEPS \
    && docker-php-ext-install pdo_mysql exif \
    && apk del .build-deps \
    && rm -rf /var/cache/apk/*

# Copy php.ini configuration
COPY php.ini-development /usr/local/etc/php/php.ini

# Set the working directory
WORKDIR /work

# Start the PHP built-in server
CMD ["php", "-S", "0.0.0.0:8000", "-t", "/work/public"]
