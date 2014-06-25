<?php

/**
 * Plugin Name: Howdy
 */

function howdy_give_me_stuff() {
	global $wpdb;

	phpinfo();

	echo "\nREQUEST:\n";
	foreach (apache_request_headers() as $header => $value) {
	    echo "$header: $value\n";
	}

	echo "\nRESPONSE:\n";
	foreach (apache_response_headers() as $header => $value) {
	    echo "$header: $value \n";
	}

	echo "\nENV:\n";
	print_r( $_ENV );

	echo "\DB SSL:\n";
	print_r( $wpdb->get_row( "SHOW STATUS LIKE 'Ssl_cipher'" ) );
	die();
}
add_action( 'wp', 'howdy_give_me_stuff', 1 );
