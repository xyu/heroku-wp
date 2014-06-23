<?php

// nananananananananananananananana BATCACHE!!!

function batcache_cancel() {
	global $batcache;

	if ( is_object($batcache) )
		$batcache->cancel = true;
}

class batcache {
	// This is the base configuration. You can edit these variables or move them into your wp-config.php file.
	var $max_age =  300; // Expire batcache items aged this many seconds (zero to disable batcache)
	
	var $remote  =    0; // Zero disables sending buffers to remote datacenters (req/sec is never sent)
	
	var $times   =    2; // Only batcache a page after it is accessed this many times... (two or more)
	var $seconds =  120; // ...in this many seconds (zero to ignore this and use batcache immediately)
	
	var $group   = 'batcache'; // Name of memcached group. You can simulate a cache flush by changing this.
	
	var $unique  = array(); // If you conditionally serve different content, put the variable values here.
	
	var $headers = array(); // Add headers here. These will be sent with every response from the cache.

	var $cache_redirects = false; // Set true to enable redirect caching.
	var $redirect_status = false; // This is set to the response code during a redirect.
	var $redirect_location = false; // This is set to the redirect location.

	var $uncached_headers = array('transfer-encoding'); // These headers will never be cached. Apply strtolower.

	var $debug        = false; // Set false to hide the batcache info <!-- comment -->
	var $debug_header = true; // Set false to hide the batcache info header

	var $cache_control = true; // Set false to disable Last-Modified and Cache-Control headers

	var $cancel = false; // Change this to cancel the output buffer. Use batcache_cancel();

	var $genlock; // Used internally
	var $do; // Used internally

	function batcache( $settings ) {
		if ( is_array( $settings ) ) foreach ( $settings as $k => $v )
			$this->$k = $v;
	}

	function is_ssl() {
		if ( isset($_SERVER['HTTPS']) ) {
			if ( 'on' == strtolower($_SERVER['HTTPS']) )
				return true;
			if ( '1' == $_SERVER['HTTPS'] )
				return true;
		} elseif ( isset($_SERVER['SERVER_PORT']) && ( '443' == $_SERVER['SERVER_PORT'] ) ) {
			return true;
		}
		return false;
	}

	function status_header( $status_header ) {
		$this->status_header = $status_header;

		return $status_header;
	}

	function redirect_status( $status, $location ) {
		if ( $this->cache_redirects ) {
			$this->redirect_status = $status;
			$this->redirect_location = $location;
		}

		return $status;
	}

	function configure_groups() {
		// Configure the memcached client
		if ( ! $this->remote )
			if ( function_exists('wp_cache_add_no_remote_groups') )
				wp_cache_add_no_remote_groups(array($this->group));
		if ( function_exists('wp_cache_add_global_groups') )
			wp_cache_add_global_groups(array($this->group));
	}

	// Defined here because timer_stop() calls number_format_i18n()
	function timer_stop($display = 0, $precision = 3) {
		global $timestart, $timeend;
		$mtime = microtime();
		$mtime = explode(' ',$mtime);
		$mtime = $mtime[1] + $mtime[0];
		$timeend = $mtime;
		$timetotal = $timeend-$timestart;
		$r = number_format($timetotal, $precision);
		if ( $display )
			echo $r;
		return $r;
	}

	function ob($output) {
		if ( $this->cancel !== false )
			return $output;

		// PHP5 and objects disappearing before output buffers?
		wp_cache_init();

		// Remember, $wp_object_cache was clobbered in wp-settings.php so we have to repeat this.
		$this->configure_groups();

		// Do not batcache blank pages unless they are HTTP redirects
		$output = trim($output);
		if ( $output === '' && (!$this->redirect_status || !$this->redirect_location) )
			return;

		// Construct and save the batcache
		$cache = array(
			'output' => $output,
			'time' => isset($_SERVER['REQUEST_TIME']) ? $_SERVER['REQUEST_TIME'] : time(),
			'timer' => $this->timer_stop(false, 3),
			'status_header' => $this->status_header,
			'redirect_status' => $this->redirect_status,
			'redirect_location' => $this->redirect_location,
			'version' => $this->url_version
		);

		if ( function_exists( 'headers_list' ) ) {
			foreach ( headers_list() as $header ) {
				list($k, $v) = array_map('trim', explode(':', $header, 2));
				$cache['headers'][$k] = $v;
			}
		} elseif ( function_exists( 'apache_response_headers' ) ) {
			$cache['headers'] = apache_response_headers();
		}

		if ( $cache['headers'] && !empty( $this->uncached_headers ) ) {
			foreach ( $cache['headers'] as $header => $value ) {
				if ( in_array( strtolower( $header ), $this->uncached_headers ) )
					unset( $cache['headers'][$header] );
			}
		}

		wp_cache_set($this->key, $cache, $this->group, $this->max_age + $this->seconds + 30);

		// Unlock regeneration
		wp_cache_delete("{$this->url_key}_genlock", $this->group);

		if ( $this->cache_control ) {
			header( 'Last-Modified: ' . gmdate( 'D, d M Y H:i:s', $cache['time'] ) . ' GMT', true );
			header("Cache-Control: max-age=$this->max_age, must-revalidate", false);
		}

		if ( !empty($this->headers) ) foreach ( $this->headers as $k => $v ) {
			if ( is_array( $v ) )
				header("{$v[0]}: {$v[1]}", false);
			else
				header("$k: $v", true);
		}

		// Add some debug info just before </head>
		if ( $this->debug ) {
			$tag = "<!--\n\tgenerated in " . $cache['timer'] . " seconds\n\t" . strlen(serialize($cache)) . " bytes batcached for " . $this->max_age . " seconds\n-->\n";
			if ( false !== $tag_position = strpos($output, '</head>') ) {
				$tag = "<!--\n\tgenerated in " . $cache['timer'] . " seconds\n\t" . strlen(serialize($cache)) . " bytes batcached for " . $this->max_age . " seconds\n-->\n";
				$output = substr($output, 0, $tag_position) . $tag . substr($output, $tag_position);
			}
		}
		if ( $this->debug_header ) {
			header(sprintf(
				"X-batcache: Caching, generated in %ums, expires in %us (%us TTL)",
				$cache['timer'] * 1000,
				$this->max_age,
                                $this->max_age
			), true);
		}

		// Pass output to next ob handler
		return $output;
	}
}
global $batcache;
// Pass in the global variable which may be an array of settings to override defaults.
$batcache = new batcache($batcache);

if ( ! defined( 'WP_CONTENT_DIR' ) )
	return;

// Never batcache interactive scripts or API endpoints.
if ( in_array(
		basename( $_SERVER['SCRIPT_FILENAME'] ),
		array(
			'wp-app.php',
			'xmlrpc.php',
			'ms-files.php',
		) ) )
	return;

// Never batcache WP javascript generators
if ( strstr( $_SERVER['SCRIPT_FILENAME'], 'wp-includes/js' ) )
	return;

// Never batcache when POST data is present.
if ( ! empty( $GLOBALS['HTTP_RAW_POST_DATA'] ) || ! empty( $_POST ) )
	return;

// Never batcache when cookies indicate a cache-exempt visitor.
if ( is_array( $_COOKIE) && ! empty( $_COOKIE ) )
	foreach ( array_keys( $_COOKIE ) as $batcache->cookie )
		if ( $batcache->cookie != 'wordpress_test_cookie' && ( substr( $batcache->cookie, 0, 2 ) == 'wp' || substr( $batcache->cookie, 0, 9 ) == 'wordpress' || substr( $batcache->cookie, 0, 14 ) == 'comment_author' ) )
			return;

if ( ! include_once( WP_CONTENT_DIR . '/object-cache.php' ) )
	return;

wp_cache_init(); // Note: wp-settings.php calls wp_cache_init() which clobbers the object made here.

if ( ! is_object( $wp_object_cache ) )
	return;

// Now that the defaults are set, you might want to use different settings under certain conditions.

/* Example: if your documents have a mobile variant (a different document served by the same URL) you must tell batcache about the variance. Otherwise you might accidentally cache the mobile version and serve it to desktop users, or vice versa.
$batcache->unique['mobile'] = is_mobile_user_agent();
*/

/* Example: never batcache for this host
if ( $_SERVER['HTTP_HOST'] == 'do-not-batcache-me.com' )
	return;
*/

/* Example: batcache everything on this host regardless of traffic level
if ( $_SERVER['HTTP_HOST'] == 'always-batcache-me.com' ) {
	$batcache->max_age = 600; // Cache for 10 minutes
	$batcache->seconds = $batcache->times = 0; // No need to wait till n number of people have accessed the page, cache instantly
}
*/

/* Example: If you sometimes serve variants dynamically (e.g. referrer search term highlighting) you probably don't want to batcache those variants. Remember this code is run very early in wp-settings.php so plugins are not yet loaded. You will get a fatal error if you try to call an undefined function. Either include your plugin now or define a test function in this file.
if ( include_once( 'plugins/searchterm-highlighter.php') && referrer_has_search_terms() )
	return;
*/

// Disabled
if ( $batcache->max_age < 1 )
	return;

// Make sure we can increment. If not, turn off the traffic sensor.
if ( ! method_exists( $GLOBALS['wp_object_cache'], 'incr' ) )
	$batcache->times = 0;

// Necessary to prevent clients using cached version after login cookies set. If this is a problem, comment it out and remove all Last-Modified headers.
header('Vary: Cookie', false);

// Things that define a unique page.
if ( isset( $_SERVER['QUERY_STRING'] ) )
	parse_str($_SERVER['QUERY_STRING'], $batcache->query);
$batcache->keys = array(
	'host' => $_SERVER['HTTP_HOST'],
	'method' => $_SERVER['REQUEST_METHOD'],
	'path' => ( $batcache->pos = strpos($_SERVER['REQUEST_URI'], '?') ) ? substr($_SERVER['REQUEST_URI'], 0, $batcache->pos) : $_SERVER['REQUEST_URI'],
	'query' => $batcache->query,
	'extra' => $batcache->unique
);

if ( $batcache->is_ssl() )
	$batcache->keys['ssl'] = true;

$batcache->configure_groups();

// Generate the batcache key
$batcache->key = md5(serialize($batcache->keys));

// Generate the traffic threshold measurement key
$batcache->req_key = $batcache->key . '_req';

// Get the batcache
$batcache->cache = wp_cache_get($batcache->key, $batcache->group);

// Are we only caching frequently-requested pages?
if ( $batcache->seconds < 1 || $batcache->times < 2 ) {
	$batcache->do = true;
} else {
	// No batcache item found, or ready to sample traffic again at the end of the batcache life?
	if ( !is_array($batcache->cache) || time() >= $batcache->cache['time'] + $batcache->max_age - $batcache->seconds ) {
		wp_cache_add($batcache->req_key, 0, $batcache->group);
		$batcache->requests = wp_cache_incr($batcache->req_key, 1, $batcache->group);

		if ( $batcache->requests >= $batcache->times )
			$batcache->do = true;
		else
			$batcache->do = false;
	}
}

// Recreate the permalink from the URL
$batcache->permalink = 'http://' . $batcache->keys['host'] . $batcache->keys['path'] . ( isset($batcache->keys['query']['p']) ? "?p=" . $batcache->keys['query']['p'] : '' );
$batcache->url_key = md5($batcache->permalink);
$batcache->url_version = (int) wp_cache_get("{$batcache->url_key}_version", $batcache->group);

// If the document has been updated and we are the first to notice, regenerate it.
if ( $batcache->do !== false && isset($batcache->cache['version']) && $batcache->cache['version'] < $batcache->url_version )
	$batcache->genlock = wp_cache_add("{$batcache->url_key}_genlock", 1, $batcache->group);

// Did we find a batcached page that hasn't expired?
if ( isset($batcache->cache['time']) && ! $batcache->genlock && time() < $batcache->cache['time'] + $batcache->max_age ) {
	// Issue redirect if cached and enabled
	if ( $batcache->cache['redirect_status'] && $batcache->cache['redirect_location'] && $batcache->cache_redirects ) {
		$status = $batcache->cache['redirect_status'];
		$location = $batcache->cache['redirect_location'];
		// From vars.php
		$is_IIS = (strpos($_SERVER['SERVER_SOFTWARE'], 'Microsoft-IIS') !== false || strpos($_SERVER['SERVER_SOFTWARE'], 'ExpressionDevServer') !== false);
		if ( $is_IIS ) {
			header("Refresh: 0;url=$location");
		} else {
			if ( php_sapi_name() != 'cgi-fcgi' ) {
				$texts = array(
					300 => 'Multiple Choices',
					301 => 'Moved Permanently',
					302 => 'Found',
					303 => 'See Other',
					304 => 'Not Modified',
					305 => 'Use Proxy',
					306 => 'Reserved',
					307 => 'Temporary Redirect',
				);
				$protocol = $_SERVER["SERVER_PROTOCOL"];
				if ( 'HTTP/1.1' != $protocol && 'HTTP/1.0' != $protocol )
					$protocol = 'HTTP/1.0';
				if ( isset($texts[$status]) )
					header("$protocol $status " . $texts[$status]);
				else
					header("$protocol 302 Found");
			}
			header("Location: $location");
		}
		exit;
	}

	// Issue "304 Not Modified" only if the dates match exactly.
	if ( $batcache->cache_control && isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) ) {
		$since = strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']);
		if ( isset($batcache->cache['headers']['Last-Modified']) )
			$batcache->cache['time'] = strtotime( $batcache->cache['headers']['Last-Modified'] );
		if ( $batcache->cache['time'] == $since ) {
			header('Cache-Control: max-age=' . ($batcache->max_age - time() + $batcache->cache['time']) . ', must-revalidate', true);
			header('Last-Modified: ' . $_SERVER['HTTP_IF_MODIFIED_SINCE'], true, 304);
			exit;
		}
	}

	// Use the batcache save time for Last-Modified so we can issue "304 Not Modified"
	if ( $batcache->cache_control ) {
		header( 'Last-Modified: ' . gmdate( 'D, d M Y H:i:s', $batcache->cache['time'] ) . ' GMT', true );
		header('Cache-Control: max-age=' . ($batcache->max_age - time() + $batcache->cache['time']) . ', must-revalidate', true);
	}

	// Add some debug info just before </head>
	if ( $batcache->debug ) {
		if ( false !== $tag_position = strpos($batcache->cache['output'], '</head>') ) {
			$tag = "<!--\n\tgenerated " . (time() - $batcache->cache['time']) . " seconds ago\n\tgenerated in " . $batcache->cache['timer'] . " seconds\n\tserved from batcache in " . $batcache->timer_stop(false, 3) . " seconds\n\texpires in " . ($batcache->max_age - time() + $batcache->cache['time']) . " seconds\n-->\n";
			$batcache->cache['output'] = substr($batcache->cache['output'], 0, $tag_position) . $tag . substr($batcache->cache['output'], $tag_position);
		}
	}
	if ( $batcache->debug_header ) {
		header(sprintf(
			"X-batcache: Cached, generated in %ums, expires in %us (%us TTL), served in %ums",
			$batcache->cache['timer'] * 1000,
			$batcache->max_age - time() + $batcache->cache['time'],
                        $batcache->max_age,
			$batcache->timer_stop(false, 3) * 1000
		), true);
	}

	if ( !empty($batcache->cache['headers']) ) foreach ( $batcache->cache['headers'] as $k => $v )
		header("$k: $v", true);

	if ( !empty($batcache->headers) ) foreach ( $batcache->headers as $k => $v ) {
		if ( is_array( $v ) )
			header("{$v[0]}: {$v[1]}", false);
		else
			header("$k: $v", true);
	}

	if ( !empty($batcache->cache['status_header']) )
		header($batcache->cache['status_header'], true);

	// Have you ever heard a death rattle before?
	die($batcache->cache['output']);
}

// Didn't meet the minimum condition?
if ( !$batcache->do && !$batcache->genlock )
	return;

$wp_filter['status_header'][10]['batcache'] = array( 'function' => array(&$batcache, 'status_header'), 'accepted_args' => 1 );
$wp_filter['wp_redirect_status'][10]['batcache'] = array( 'function' => array(&$batcache, 'redirect_status'), 'accepted_args' => 2 );

ob_start(array(&$batcache, 'ob'));

// It is safer to omit the final PHP closing tag.

