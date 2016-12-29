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

// Setup autoload
require '/app/vendor/autoload.php';

// Disable filesystem level changes from WP
define( 'DISALLOW_FILE_EDIT', true );
define( 'DISALLOW_FILE_MODS', true );

// Make sure we admin over SSL
define( 'FORCE_SSL_LOGIN', true );
define( 'FORCE_SSL_ADMIN', true );

// HTTPS port is always 80 because SSL is terminated at Heroku router / CloudFlare
define( 'JETPACK_SIGNATURE__HTTPS_PORT', 80 );

/**
 * Redis settings.
 */
if ( !empty( $_ENV['REDIS_URL'] ) ) {
	$_redissettings = parse_url( $_ENV['REDIS_URL'] );

	define( 'WP_CACHE', true );
	define( 'WP_REDIS_CLIENT',   'predis'                  );
	define( 'WP_REDIS_SCHEME',   $_redissettings['scheme'] );
	define( 'WP_REDIS_HOST',     $_redissettings['host']   );
	define( 'WP_REDIS_PORT',     $_redissettings['port']   );
	define( 'WP_REDIS_PASSWORD', $_redissettings['pass']   );
	define( 'WP_REDIS_MAXTTL',   2419200 /* 28 days */     );

	unset( $_redissettings );
}

/**
 * MySQL settings.
 *
 * We are getting Heroku ClearDB settings from Heroku Environment Vars
 */

// MySQL settings: always compress
$_dbflags = MYSQLI_CLIENT_COMPRESS;

// MySQL settings: turn on SSL?
if ( isset( $_ENV['WP_DB_SSL'] ) && 'ON' == $_ENV['WP_DB_SSL'] ) {
	$_dbflags |= MYSQLI_CLIENT_SSL;
}

if ( isset( $_ENV['WP_DB_URL'] ) ) {
	$_dbsettings = parse_url( $_ENV['WP_DB_URL'] );

	// Use RDS CA for Jaws DB / most default installs
	if ( empty( $_ENV[ 'MYSQL_SSL_CA' ] ) ) {
		$_ENV[ 'MYSQL_SSL_CA' ] = 'rds-combined-ca-bundle.pem';
	}
} elseif ( isset( $_ENV['CLEARDB_DATABASE_URL'] ) ) {
	$_dbsettings = parse_url( $_ENV['CLEARDB_DATABASE_URL'] );

	// Use ClearDB CA for Clear DB
	if ( empty( $_ENV[ 'MYSQL_SSL_CA' ] ) ) {
		$_ENV[ 'MYSQL_SSL_CA' ] = 'cleardb-ca.pem';
	}
	// ClearDB signs with an invalid CN
	$_dbflags |= MYSQLI_CLIENT_SSL_DONT_VERIFY_SERVER_CERT;
} else {
	$_dbsettings = parse_url( 'mysql://herokuwp:password@127.0.0.1/herokuwp' );
}

define( 'DB_NAME',              trim( $_dbsettings['path'], '/' ) );
define( 'DB_USER',              $_dbsettings['user']              );
define( 'DB_PASSWORD',          $_dbsettings['pass']              );
define( 'DB_HOST',              $_dbsettings['host']              );
define( 'DB_CHARSET',           'utf8'                            );
define( 'DB_COLLATE',           ''                                );
define( 'WP_USE_EXT_MYSQL',     false /* Always use MySQLi */     );
define( 'MYSQL_CLIENT_FLAGS',   $_dbflags                         );

// Set client keys and certs for X509 auth or explicit server CA if they exist in ENV vars
$_dbsslpaths = array(
	'MYSQL_SSL_KEY',
	'MYSQL_SSL_CERT',
	'MYSQL_SSL_CA'
);
foreach ( $_dbsslpaths as $_dbsslpath ) {
	if ( !empty( $_ENV[ $_dbsslpath ] ) ) {
		define( $_dbsslpath, '/app/support/mysql-certs/' . $_ENV[ $_dbsslpath ] );
	}
}

unset( $_dbsettings, $_dbflags, $_dbsslpaths, $_dbsslpath );

/**
 * SendGrid settings.
 */
if ( !empty( $_ENV['SENDGRID_USERNAME'] ) && !empty( $_ENV['SENDGRID_PASSWORD'] ) ) {
	define( 'SENDGRID_AUTH_METHOD', 'credentials'              );
	define( 'SENDGRID_USERNAME',    $_ENV['SENDGRID_USERNAME'] );
	define( 'SENDGRID_PASSWORD',    $_ENV['SENDGRID_PASSWORD'] );
}

/**
 * S3-Uploads settings
 *
 * AWS_S3_URL should be in the form of one of the following:
 *   s3://KEY:SECRET@s3.amazonaws.com/BUCKET
 *   s3://KEY:SECRET@s3-REGION.amazonaws.com/BUCKET (with optional region)
 *   s3://KEY:SECRET@s3.amazonaws.com/BUCKET?url=https://example.com (to set a prettier bucket URL / alias)
 */
if ( !empty( $_ENV['AWS_S3_URL'] ) ) {
	$_awssettings = array();
	$_awsquery = array();

	$_awsmatch = array();
	if ( preg_match( '/^s3:\/\/([^:]+):([a-zA-Z0-9+\/]+)@(s3[0-9a-z-]*\.amazonaws\.com.*)$/', $_ENV['AWS_S3_URL'], $_awsmatch ) ) {
		// Non-conforming URL fix it then parse
		$_awssettings = parse_url( sprintf(
			"s3://%s:%s@%s",
			urlencode( $_awsmatch[1] ),
			urlencode( $_awsmatch[2] ),
			$_awsmatch[3]
		) );
		$_awsmatch = array();
	} else {
		// Properly URL encoded base64 encoded string just parse
		$_awssettings = parse_url(
			$_ENV['AWS_S3_URL']
		);
	}

	define( 'S3_UPLOADS_KEY',    urldecode( $_awssettings['user'] ) );
	define( 'S3_UPLOADS_SECRET', urldecode( $_awssettings['pass'] ) );
	define( 'S3_UPLOADS_BUCKET', trim( $_awssettings['path'], '/' ) );

	$_awsmatch = array();
	if ( preg_match( '/^s3(-|\.dualstack\.)([0-9a-z-]+)\.amazonaws\.com$/', $_awssettings['host'], $_awsmatch ) ) {
		define( 'S3_UPLOADS_REGION', $_awsmatch[2] );
	}

	if ( !empty( $_awssettings['query'] ) ) {
		parse_str( $_awssettings['query'], $_awsquery );
		if ( !empty( $_awsquery['url'] ) ) {
			define( 'S3_UPLOADS_BUCKET_URL', $_awsquery['url'] );
		}
	}

	unset( $_awssettings, $_awsquery, $_awsmatch );
}

/**
 * Authentication Unique Keys and Salts.
 *
 * You can change these at any point in time to invalidate all existing cookies.
 * This will force all users to have to log in again.
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

/**
 * Configure Batcache
 */
$batcache = array(
	'debug' => false,
	'debug_header' => true,
	'cache_control' => true,
	'use_stale' => true,
	'cache_redirects' => true,
	'group' => 'batcache',
);

/**
 * Disable WP Cron if we are using an external service for this
 */
if ( isset( $_ENV['DISABLE_WP_CRON'] ) && 'TRUE' == $_ENV['DISABLE_WP_CRON'] ) {
	define( 'DISABLE_WP_CRON', true );
}

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
if ( isset( $_ENV['WP_DEBUG'] ) && 'TRUE' == $_ENV['WP_DEBUG'] ) {
	// Turn on debug, log to default destination and don't display
	define( 'WP_DEBUG', true );
	define( 'WP_DEBUG_LOG', false );
	define( 'WP_DEBUG_DISPLAY', false );
} else {
	define( 'WP_DEBUG', false );
}

/* That's all, stop editing! Happy blogging. */

/** Absolute path to the WordPress directory. */
if ( !defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', dirname( __FILE__ ) . '/' );
}

/** Sets up WordPress vars and included files. */
require_once( ABSPATH . 'wp-settings.php' );
