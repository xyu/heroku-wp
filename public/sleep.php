<?php

	sleep( (int) $_GET['s'] );

	echo file_get_contents( '/app/tmp.txt' );

	if ( !empty( $_GET['t'] ) ) {
		file_put_contents( '/app/tmp.txt', $_GET['t'] );
	}
