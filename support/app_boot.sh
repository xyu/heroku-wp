#!/bin/bash

# Write certs in env to files and replace with path
if [ -n "$CLEARDB_SSL_KEY" -a -n "$CLEARDB_SSL_CERT" -a -n "$CLEARDB_SSL_CA" ]
then
  echo "ClearDB MySQL SSL keys gotten from env vars"
  mkdir "/app/certs"
  echo "$CLEARDB_SSL_KEY" > /app/certs/key.pem
  echo "$CLEARDB_SSL_CERT" > /app/certs/cert.pem
  echo "$CLEARDB_SSL_CA" > /app/certs/ca.pem
  export CLEARDB_SSL_KEY="/app/certs/key.pem"
  export CLEARDB_SSL_CERT="/app/certs/cert.pem"
  export CLEARDB_SSL_CA="/app/certs/ca.pem"
  export WP_DB_SSL="ON"
fi

# Boot up!
vendor/bin/heroku-php-nginx \
  -C support/nginx.inc.conf \
  -F support/php-fpm.inc.conf \
  -i support/php.ini \
  public.built/
