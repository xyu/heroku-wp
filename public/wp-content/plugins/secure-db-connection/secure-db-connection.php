<?php
/*
Plugin name: Secure DB Connection
Plugin URI: http://wordpress.org/plugins/secure-db-connection/
Description: Sets SSL keys and certs for encrypted database connections
Author: Xiao Yu
Author URI: http://xyu.io/
Version: 1.0
*/

if ( ! defined( 'ABSPATH' ) ) exit;

class WP_SecureDBConnection {

	private $_ver = '1.0';

	public function __construct() {
		$this->init();
	}

	public function init() {
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_styles' ) );
		register_deactivation_hook( __FILE__, array( $this, 'on_deactivation' ) );
	}

	public function enqueue_admin_styles( $hook_suffix ) {
		echo $hook_suffix;
		if ( "" === $hook_suffix ) {
			$plugin = get_plugin_data( __FILE__ );
			wp_enqueue_style(
				'secure-db-connection',
				plugin_dir_url( __FILE__ ) . 'includes/admin-page.css',
				null,
				$plugin[ 'Version' ]
			);
		}
	}

	public function on_deactivation() {
		global $wp_filesystem;
		global $wpdb;

		if ( file_exists( WP_CONTENT_DIR . '/db.php' ) && ( $wpdb instanceof wpdb_ssl ) ) {
			if ( WP_Filesystem( request_filesystem_credentials( '' ) ) ) {
				$wp_filesystem->delete( WP_CONTENT_DIR . '/db.php' );
			}
		}
	}

}

new WP_SecureDBConnection();
