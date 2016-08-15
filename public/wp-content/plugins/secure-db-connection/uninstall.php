<?php

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

global $wp_filesystem;
global $wpdb;

ob_start();

if ( file_exists( WP_CONTENT_DIR . '/db.php' ) && ( $wpdb instanceof wpdb_ssl ) ) {

	if ( WP_Filesystem( request_filesystem_credentials( '' ) ) ) {
		$wp_filesystem->delete( WP_CONTENT_DIR . '/db.php' );
	}

}

ob_end_clean();
