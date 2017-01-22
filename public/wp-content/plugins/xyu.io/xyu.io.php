<?php
 /**
 * Plugin name: xyu.io
 * Plugin URI: https://xyu.io/
 * Description: Site customizations for xyu.io
 * Author: Xiao Yu
 * Author URI: https://xyu.io/
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WP_SiteCustomizations_XYUIO {

	public static function load() {
		add_action( 'wp_enqueue_scripts', array( self::_get_funcs(), 'use_native_fonts' ), 20 );
		add_filter( 'jetpack_photon_domain', array( self::_get_funcs(), 'lock_photon_domain' ) );
	}

	public static function activation() {
	}

	public static function deactivation() {
	}

	public static function uninstall() {
	}

	private static $_funcs;
	private static function _get_funcs() {
		if ( ! self::$_funcs instanceof WP_SiteCustomizations_XYUIO_Funcs ) {
			self::$_funcs = new WP_SiteCustomizations_XYUIO_Funcs();
		}
		return self::$_funcs;
	}

}

class WP_SiteCustomizations_XYUIO_Funcs {

	public function use_native_fonts() {
		wp_dequeue_style( 'twentyfifteen-fonts' );
	}

	public function lock_photon_domain( $domain ) {
		$expected_domains = array(
			'https://i0.wp.com',
			'https://i1.wp.com',
			'https://i2.wp.com',
		);

		if ( in_array( $domain, $expected_domains ) ) {
			$domain = 'https://i0.wp.com';
		}

		return $domain;
	}

}

add_action( 'plugins_loaded', 'WP_SiteCustomizations_XYUIO::load' );

register_activation_hook(   __FILE__, 'WP_SiteCustomizations_XYUIO::activation'   );
register_deactivation_hook( __FILE__, 'WP_SiteCustomizations_XYUIO::deactivation' );
register_uninstall_hook(    __FILE__, 'WP_SiteCustomizations_XYUIO::uninstall'    );
