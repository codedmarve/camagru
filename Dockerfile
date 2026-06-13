FROM php:8.2-apache

# Install PHP extensions and msmtp for sending emails
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    msmtp \
    msmtp-mta \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install gd pdo pdo_mysql

# Tell PHP to use msmtp for sending mail
RUN echo 'sendmail_path = "/usr/bin/msmtp -t"' > /usr/local/etc/php/conf.d/mail.ini

# The msmtp config (/etc/msmtprc) is generated at startup from environment
# variables by the entrypoint, so the mail provider is set via .env (Mailhog in
# dev, a real SMTP relay in production) without rebuilding the image.
COPY docker/entrypoint.sh /usr/local/bin/camagru-entrypoint.sh
RUN chmod +x /usr/local/bin/camagru-entrypoint.sh

# Enable Apache mod_rewrite for clean URLs
RUN a2enmod rewrite

# Set document root to public folder
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# Allow .htaccess overrides
RUN sed -i '/<Directory \/var\/www\/>/,/<\/Directory>/ s/AllowOverride None/AllowOverride All/' /etc/apache2/apache2.conf

WORKDIR /var/www/html

# Generate msmtprc from env, then start Apache (base image's default CMD)
ENTRYPOINT ["camagru-entrypoint.sh"]
CMD ["apache2-foreground"]
