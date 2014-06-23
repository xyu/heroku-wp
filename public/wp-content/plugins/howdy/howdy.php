<?php
	phpinfo();

	// try out memcache
	Memcached::setSaslAuthData( $ENV['MEMCACHIER_USERNAME'], $ENV['MEMCACHIER_PASSWORD'] );

	$m_server = parse_url( $ENV['MEMCACHIER_SERVERS'] );
	$m = new Memcached();

	var_dump(
		$m->addServer( $m_server['host'], $m_server['port'] )
	);

	var_dump(
		$m
	);

	var_dump( $_SERVER );

	var_dump( $_ENV );