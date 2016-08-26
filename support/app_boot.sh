#!/bin/bash

# Write certs in env to files and replace with path
if [ -n "$MYSQL_SSL_KEY" -a -n "$MYSQL_SSL_CERT" ]
then
  echo "MySQL client key and cert for X509 auth gotten from env vars"
  mkdir -p "/app/support/certs"
  echo "$MYSQL_SSL_KEY" > /app/support/certs/client-key.pem
  echo "$MYSQL_SSL_CERT" > /app/support/certs/client-cert.pem
  export MYSQL_SSL_KEY="/app/support/certs/client-key.pem"
  export MYSQL_SSL_CERT="/app/support/certs/client-cert.pem"
fi

if [ -n "$MYSQL_SSL_CA" ]
then
  echo "MySQL server root CA gotten from env vars"
  mkdir -p "/app/support/certs"
  echo "$MYSQL_SSL_CA" > /app/support/certs/server-ca.pem
  export MYSQL_SSL_CA="/app/support/certs/server-ca.pem"
fi

# Boot up!
vendor/bin/heroku-php-nginx \
  -C support/nginx.inc.conf \
  -F support/php-fpm.inc.conf \
  -i support/php.ini \
  public.built/
