<?php

/**
 * Plugin Name: SSL Domain Alias
 * Plugin URI: http://wordpress.stackexchange.com/questions/38902
 * Description: Use a different domain for serving your website over SSL, set with <code>SSL_DOMAIN_ALIAS</code> in your <code>wp-config.php</code>.
 * Author: TheDeadMedic, @HypertextRanch
 * Author URI: http://wordpress.stackexchange.com/users/1685/thedeadmedic
 *
 * @package SSL_Domain_Alias
 */

class wp_plugin_ssl_domain_alias {
	private $_http_domain;
	private $_https_domain;

	public function __construct($http_domain, $https_domain) {
		$this->_http_domain = $http_domain;
		$this->_https_domain = $https_domain;
	}

	/**
	 * Swap out the current site domain with {@see SSL_DOMAIN_ALIAS} if the
	 * protocol is HTTPS.
	 *
	 * This function is not bulletproof, and expects {@see SSL_DOMAIN_ALIAS} to
	 * be defined.
	 *
	 * @todo The replacement is a simple string replacement (for speed). If the
	 * domain name is matching other parts of the URL other than the host, we'll
	 * need to switch to a more rigid regex.
	 *
	 * @param string $url
	 * @return string
	 */
	public function use_secure_domain_for_https( $url ) {
	    if ( 0 === strpos( $url, 'https://' ) )
		$url = str_replace( "https://{$this->_http_domain}", "https://{$this->_https_domain}", $url );

	    return $url;
	}

	/**
	 * Swap out the current site domain and scheme with {@see SSL_DOMAIN_ALIAS} if the
	 * protocol is HTTP.
	 *
	 * This function is not bulletproof, and expects {@see SSL_DOMAIN_ALIAS} to
	 * be defined.
	 *
	 * @todo The replacement is a simple string replacement (for speed). If the
	 * domain name is matching other parts of the URL other than the host, we'll
	 * need to switch to a more rigid regex.
	 *
	 * @param string $url
	 * @return string
	 */
	public function use_secure_domain_for_http( $url ) {
	    if ( 0 === strpos( $url, 'http://' ) )
		$url = str_replace( "http://{$this->_http_domain}", "https://{$this->_https_domain}", $url );

	    return $url;
	}
}

if ( defined( 'SSL_DOMAIN_ALIAS' ) && '' != SSL_DOMAIN_ALIAS ) {
	$wp_plugin_ssl_domain_alias = new wp_plugin_ssl_domain_alias(
		parse_url( get_option( 'siteurl' ), PHP_URL_HOST ),
		SSL_DOMAIN_ALIAS
	);

	add_filter( 'plugins_url', array( $wp_plugin_ssl_domain_alias, 'use_secure_domain_for_https' ), 1 );
	add_filter( 'content_url', array( $wp_plugin_ssl_domain_alias, 'use_secure_domain_for_https' ), 1 );
	add_filter( 'site_url', array( $wp_plugin_ssl_domain_alias, 'use_secure_domain_for_https' ), 1 );
//	add_filter( 'preview_post_link', array( $wp_plugin_ssl_domain_alias, 'use_secure_domain_for_http' ), 1 );
}

