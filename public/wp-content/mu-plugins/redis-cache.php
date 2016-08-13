<?php
/*
Plugin Name: Redis Object Cache (MU)
Plugin URI: http://wordpress.org/plugins/redis-cache/
Description: A persistent object cache backend powered by Redis. Supports Predis, PhpRedis, HHVM, replication and clustering.
Version: 1.3.2+
Text Domain: redis-cache
Domain Path: /languages
Author: Till Krüss
Author URI: https://till.im/
License: GPLv3
License URI: http://www.gnu.org/licenses/gpl-3.0.html

Redis Object Cache is installed by Composer but we need a helper to
auto-activate this plugin.
*/

require WPMU_PLUGIN_DIR.'/redis-cache/redis-cache.php';
