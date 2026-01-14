FROM php:8.4-fpm

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
    git \
    unzip \
    libpq-dev \
    libicu-dev \
    ffmpeg \
    python3 \
    python3-pip \
    curl \
    ca-certificates \
    && docker-php-ext-install pdo_pgsql pgsql bcmath intl pcntl \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && curl -L https://github.com/yt-dlp/yt-dlp/releases/latest/download/yt-dlp -o /usr/local/bin/yt-dlp \
    && chmod a+rx /usr/local/bin/yt-dlp \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

RUN chown -R www-data:www-data /var/www/html
