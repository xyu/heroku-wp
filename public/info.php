<?php
	phpinfo();

	$redis = new Predis\Client(getenv('REDIS_URL'));
	var_dump( $redis );
