#!/bin/bash

# Write certs in env to files and replace with path
if [ -n "$MYSQL_SSL_KEY" -a -n "$MYSQL_SSL_CERT" ]
then
  echo "Custom MySQL client key and cert for X509 auth set"

  if [[ ! "$MYSQL_SSL_KEY" =~ '\.pem$' ]]
  then
    echo "$MYSQL_SSL_KEY" > /app/support/mysql-certs/client-key.pem
    export MYSQL_SSL_KEY="client-key.pem"
  fi

  if [[ ! "$MYSQL_SSL_CERT" =~ '\.pem$' ]]
  then
    echo "$MYSQL_SSL_CERT" > /app/support/mysql-certs/client-cert.pem
    export MYSQL_SSL_CERT="client-cert.pem"
  fi
fi

if [ -n "$MYSQL_SSL_CA" ]
then
  echo "Custom MySQL server root CA set"

  if [[ ! "$MYSQL_SSL_CA" =~ '\.pem$' ]]
  then
    echo "$MYSQL_SSL_CA" > /app/support/mysql-certs/server-ca.pem
    export MYSQL_SSL_CA="server-ca.pem"
  fi
fi

#
# Try and fix file modified times to always be set to slug compile time
#
SLUG_MTIME=$( sed -n -e 's/^Slug Compiled : \(.*\)$/\1/p' public.built/.heroku-wp | head -n 1 )
if [ -n "$SLUG_MTIME" ]
then
  for ITEM in $( find public.built )
  do
    [ -f "$ITEM" ] && touch -m --date="$SLUG_MTIME" "$ITEM"
  done
fi

# Write out boot timestamp
NOW=$( date )
echo "Dyno Booted   : $NOW" >> public.built/.heroku-wp

# Boot up!
vendor/bin/heroku-php-nginx \
  -C support/nginx.inc.conf \
  -F support/php-fpm.inc.conf \
  -i support/php.ini \
  public.built/
