<?php
	phpinfo();

	// try out memcache
	$m = new Memcached();

	$m->addServer( 'cache-aws-us-east-1.iron.io', 11211 );
	$m->set('oauth', "{$ENV['IRON_CACHE_TOKEN']} {$ENV['IRON_CACHE_PROJECT_ID']} WP", 0);

	$m->set( 'test', 'blagh', 1 );
	var_dump( $m->get( 'test' ) );
	sleep(1);
	var_dump( $m->get( 'test' ) );
