#!/bin/sh
set -e

# Generate the msmtp configuration from environment variables at container
# startup, so the mail provider is chosen via .env with no image rebuild:
#   - dev:  MAIL_HOST=mailhog  MAIL_PORT=1025  MAIL_TLS=off  MAIL_AUTH=off
#   - prod: a real SMTP relay with MAIL_TLS=on, MAIL_AUTH=on and credentials.
{
    echo "account default"
    echo "host ${MAIL_HOST:-mailhog}"
    echo "port ${MAIL_PORT:-1025}"
    echo "from ${MAIL_FROM:-noreply@camagru.local}"
    echo "auto_from off"
    echo "tls ${MAIL_TLS:-off}"
    if [ "${MAIL_TLS:-off}" = "on" ]; then
        echo "tls_starttls on"
        echo "tls_trust_file /etc/ssl/certs/ca-certificates.crt"
    fi
    echo "auth ${MAIL_AUTH:-off}"
    if [ "${MAIL_AUTH:-off}" = "on" ]; then
        echo "user ${MAIL_USER}"
        echo "password ${MAIL_PASS}"
    fi
} > /etc/msmtprc

# msmtp refuses a config that contains a password if it is group/world readable.
# Owned by www-data (Apache/PHP runs mail() as that user) with mode 600.
chown www-data:www-data /etc/msmtprc
chmod 600 /etc/msmtprc

# Hand off to the base image's entrypoint (runs apache2-foreground via CMD).
exec docker-php-entrypoint "$@"
