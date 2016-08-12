<?php
/**
 * The base configurations of the WordPress.
 *
 * This file has the following configurations: MySQL settings, Table Prefix,
 * Secret Keys, WordPress Language, and ABSPATH. You can find more information
 * by visiting {@link http://codex.wordpress.org/Editing_wp-config.php Editing
 * wp-config.php} Codex page. You can get the MySQL settings from your web host.
 *
 * This file is used by the wp-config.php creation script during the
 * installation. You don't have to use the web site, you can just copy this file
 * to "wp-config.php" and fill in the values.
 *
 * @package WordPress
 */

// Disable filesystem level changes from WP
define( 'DISALLOW_FILE_EDIT', true );
define( 'DISALLOW_FILE_MODS', true );

// Set SSL'ed domain
if ( !empty( $_ENV['SSL_DOMAIN'] ) ) {
	define( 'FORCE_SSL_LOGIN', true );
	define( 'FORCE_SSL_ADMIN', true );
}

// HTTPS port is always 80 because SSL is terminated at Heroku router / CloudFlare
define( 'JETPACK_SIGNATURE__HTTPS_PORT', 80 );

/**#@+
 * Memcache settings.
 */
if ( !empty( $_ENV['MEMCACHIER_SERVERS'] ) ) {
	$_mcsettings = parse_url( $_ENV['MEMCACHIER_SERVERS'] );

	define( 'WP_CACHE', true );
	$sasl_memcached_config = array(
		'default' => array(
			array(
				'host' => $_mcsettings['host'],
				'port' => $_mcsettings['port'],
				'user' => $_ENV['MEMCACHIER_USERNAME'],
				'pass' => $_ENV['MEMCACHIER_PASSWORD'],
			),
		),
	);

	unset( $_mcsettings );
}

/**#@-*/

/**#@+
 * MySQL settings.
 *
 * We are getting Heroku ClearDB settings from Heroku Environment Vars
 */
if ( isset( $_ENV['CLEARDB_DATABASE_URL'] ) ) {
	$_dbsettings = parse_url( $_ENV['CLEARDB_DATABASE_URL'] );
} else {
	$_dbsettings = parse_url( 'mysql://herokuwp:password@127.0.0.1/herokuwp' );
}

define( 'DB_NAME',     trim( $_dbsettings['path'], '/' ) );
define( 'DB_USER',     $_dbsettings['user']              );
define( 'DB_PASSWORD', $_dbsettings['pass']              );
define( 'DB_HOST',     $_dbsettings['host']              );
define( 'DB_CHARSET', 'utf8'                             );
define( 'DB_COLLATE', ''                                 );

unset( $_dbsettings );

// Set SSL settings
if ( isset( $_ENV['CLEARDB_SSL'] ) && 'ON' == $_ENV['CLEARDB_SSL'] ) {
	define( 'MYSQL_CLIENT_FLAGS', MYSQLI_CLIENT_COMPRESS | MYSQLI_CLIENT_SSL );
	define( 'MYSQL_SSL_KEY',      $_ENV['CLEARDB_SSL_KEY']                   );
	define( 'MYSQL_SSL_CERT',     $_ENV['CLEARDB_SSL_CERT']                  );
	define( 'MYSQL_SSL_CA',       $_ENV['CLEARDB_SSL_CA']                    );
} else {
	define( 'MYSQL_CLIENT_FLAGS', MYSQLI_CLIENT_COMPRESS                     );
}

// Disable ext/mysql and use mysqli
define( 'WP_USE_EXT_MYSQL', false );

/**#@-*/

/**#@+
 * Authentication Unique Keys and Salts.
 *
 * Change these to different unique phrases!
 * You can generate these using the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}
 * You can change these at any point in time to invalidate all existing cookies. This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
$_saltKeys = array(
	'AUTH_KEY',
	'SECURE_AUTH_KEY',
	'LOGGED_IN_KEY',
	'NONCE_KEY',
	'AUTH_SALT',
	'SECURE_AUTH_SALT',
	'LOGGED_IN_SALT',
	'NONCE_SALT',
);

foreach ( $_saltKeys as $_saltKey ) {
	if ( !defined( $_saltKey ) ) {
		define(
			$_saltKey,
			empty( $_ENV[ $_saltKey ] ) ? 'herokuwp' : $_ENV[ $_saltKey ]
		);
	}
}

unset( $_saltKeys, $_saltKey );

/**#@-*/

/**
 * WordPress Database Table prefix.
 *
 * You can have multiple installations in one database if you give each a unique
 * prefix. Only numbers, letters, and underscores please!
 */
$table_prefix  = 'wp_';

/**
 * WordPress Localized Language, defaults to English.
 *
 * Change this to localize WordPress. A corresponding MO file for the chosen
 * language must be installed to wp-content/languages. For example, install
 * de_DE.mo to wp-content/languages and set WPLANG to 'de_DE' to enable German
 * language support.
 */
define( 'WPLANG', '' );

/**
 * For developers: WordPress debugging mode.
 *
 * Change this to true to enable the display of notices during development.
 * It is strongly recommended that plugin and theme developers use WP_DEBUG
 * in their development environments.
 */
define( 'WP_DEBUG', true );

/* That's all, stop editing! Happy blogging. */

/** Absolute path to the WordPress directory. */
if ( !defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', dirname( __FILE__ ) . '/' );
}

/** Sets up WordPress vars and included files. */
require_once( ABSPATH . 'wp-settings.php' );
