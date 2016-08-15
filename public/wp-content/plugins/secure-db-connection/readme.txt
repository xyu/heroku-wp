=== Secure DB Connection ===
Contributors: HypertextRanch
Tags: db, mysql, secure, encrypted, ssl
Requires at least: 3.9
Tested up to: 4.6
Stable tag: 1.0
License: GPLv3
License URI: http://www.gnu.org/licenses/gpl-3.0.html

Sets SSL keys and certs for encrypted database connections.

== Description ==

Depending on the MySQL server setup the SSL certs used may not be in the trusted store, if that's the case `mysqli_ssl_set()` needs to be called to set custom keys and certs before connect. This Plugin adds a custom DB class that allows these settings to be configured via custom constants.

This plugin will also add a custom item on the "At a Glance" section of the Dashboard to show if the `$wpdb` connection is secure or not.

Also find me on [GitHub](https://github.com/xyu/secure-db-connection).

= Configuration Parameters =

To adjust the configuration, define any of the following applicable constants in your `wp-config.php` file.

  * `MYSQL_SSL_KEY` [default: not set]

    The path name to the key file. (RSA Key)

  * `MYSQL_SSL_CERT` [default: not set]

    The path name to the certificate file.

  * `MYSQL_SSL_CA` [default: not set]

    The path name to the certificate authority file.

  * `MYSQL_SSL_CA_PATH` [default: not set]

    The pathname to a directory that contains trusted SSL CA certificates in PEM format.

  * `MYSQL_SSL_CIPHER` [default: not set]

    A list of allowable ciphers to use for SSL encryption

== Installation ==

For detailed installation instructions, please read the [standard installation procedure for WordPress plugins](http://codex.wordpress.org/Managing_Plugins#Installing_Plugins).

1. Install and activate plugin.
1. Set the relevent paths in your `wp-config.php` file.

If you server doesn't support the [WordPress Filesystem API](https://codex.wordpress.org/Filesystem_API), you have to manually copy the `db.php` file from the `/plugins/secure-db-connection/includes/` directory to the `/wp-content/` directory.

== Changelog ==

= 1.0 =

  * Initial release
