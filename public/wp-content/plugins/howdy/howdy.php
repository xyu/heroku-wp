<?php
	phpinfo();

	// try out memcache
	Memcached::addServer( 'cache-aws-us-east-1.iron.io', 11211 );
	Memcached.set('oauth', "{$ENV['IRON_CACHE_TOKEN']} {$ENV['IRON_CACHE_PROJECT_ID']} WP", 0);

	$m = new Memcached();

	var_dump(
		$m
	);

	$m->set( 'test', 'blagh', 1 );
	var_dump( $m->get( 'test' ) );
	sleep(1);
	var_dump( $m->get( 'test' ) );