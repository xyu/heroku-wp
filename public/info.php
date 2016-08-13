<?php
	require __DIR__ . '/../vendor/autoload.php';

	phpinfo();

	$redis = new Predis\Client(getenv('REDIS_URL'));
	var_dump( $redis );
